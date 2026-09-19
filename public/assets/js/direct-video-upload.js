/**
 * Direct-to-S3 video upload for the admin video forms.
 *
 * The video used to travel inside the form POST and nginx answered 413
 * ("Payload too large") past client_max_body_size. Here the browser uploads
 * the file itself with presigned multipart URLs and the form carries only the
 * object key. Every failure path returns null so the caller can fall back to
 * the original in-form POST — behaviour must never get worse than it was.
 */
(function (window) {
    'use strict';

    var MAX_BYTES = 2147483648; // 2 GiB, matches VideoUploadSignController
    var DEFAULT_PART_SIZE = 16 * 1024 * 1024;
    var CONCURRENCY = 3;

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function post(url, payload) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf()
            },
            body: JSON.stringify(payload)
        }).then(function (res) {
            if (!res.ok) {
                throw new Error('Signing endpoint responded ' + res.status);
            }
            return res.json();
        });
    }

    /** One part, via XHR so we get byte-level progress. Resolves with the ETag. */
    function putPart(url, blob, onBytes) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            var last = 0;

            xhr.open('PUT', url, true);
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    onBytes(e.loaded - last);
                    last = e.loaded;
                }
            });
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var etag = xhr.getResponseHeader('ETag');
                    if (!etag) {
                        // Bucket CORS is missing ExposeHeaders: ETag.
                        reject(new Error('ETag header not exposed by the bucket'));
                        return;
                    }
                    onBytes(blob.size - last);
                    resolve(etag);
                } else {
                    reject(new Error('Part upload responded ' + xhr.status));
                }
            };
            xhr.onerror = function () { reject(new Error('Part upload network error')); };
            xhr.onabort = function () { reject(new Error('Part upload aborted')); };
            xhr.send(blob);
        });
    }

    /**
     * @returns {Promise<string|null>} the S3 object key, or null when the
     *          caller should fall back to the legacy in-form POST.
     */
    function directVideoUpload(file, options) {
        options = options || {};
        var onProgress = options.onProgress || function () {};
        var routes = window.directVideoUploadRoutes;

        if (!file || !routes || !window.fetch || !window.Promise || !file.slice) {
            return Promise.resolve(null);
        }
        if (file.size > MAX_BYTES) {
            return Promise.reject(new Error('TOO_LARGE'));
        }

        var session = null;

        return post(routes.create, {
            filename: file.name,
            size: file.size,
            content_type: file.type || ''
        }).then(function (res) {
            session = res;
            var partSize = res.partSize || DEFAULT_PART_SIZE;
            var total = Math.max(1, Math.ceil(file.size / partSize));
            var parts = new Array(total);
            var sent = 0;
            var next = 0;

            function report(delta) {
                sent += delta;
                onProgress(Math.min(99, Math.round((sent / file.size) * 100)));
            }

            function worker() {
                if (next >= total) {
                    return Promise.resolve();
                }
                var index = next++;
                var partNumber = index + 1;
                var blob = file.slice(index * partSize, Math.min(file.size, (index + 1) * partSize));

                return post(routes.part, {
                    key: session.key,
                    uploadId: session.uploadId,
                    partNumber: partNumber
                }).then(function (signed) {
                    return putPart(signed.url, blob, report);
                }).then(function (etag) {
                    parts[index] = { PartNumber: partNumber, ETag: etag };
                    return worker();
                });
            }

            var workers = [];
            for (var i = 0; i < Math.min(CONCURRENCY, total); i++) {
                workers.push(worker());
            }

            return Promise.all(workers).then(function () { return parts; });
        }).then(function (parts) {
            return post(routes.complete, {
                key: session.key,
                uploadId: session.uploadId,
                parts: parts
            });
        }).then(function (res) {
            onProgress(100);
            return res.key;
        }).catch(function (err) {
            if (session) {
                post(routes.abort, { key: session.key, uploadId: session.uploadId }).catch(function () {});
            }
            throw err;
        });
    }

    /**
     * Duration and a poster frame, read in the browser. The server no longer
     * sees the file, so ffmpeg cannot probe it; failures fall back to the same
     * "00:00" the server already tolerates.
     */
    function probeVideoLocally(file) {
        return new Promise(function (resolve) {
            var done = false;
            var result = { duration: null, thumbnailBlob: null };
            var url = URL.createObjectURL(file);
            var video = document.createElement('video');

            function finish() {
                if (done) { return; }
                done = true;
                URL.revokeObjectURL(url);
                resolve(result);
            }

            var timer = setTimeout(finish, 15000);

            video.preload = 'metadata';
            video.muted = true;
            video.playsInline = true;
            video.onerror = function () { clearTimeout(timer); finish(); };

            var sought = false;

            function readDuration() {
                if (result.duration || !isFinite(video.duration) || video.duration <= 0) {
                    return false;
                }
                var total = Math.round(video.duration);
                result.duration = String(Math.floor(total / 60)).padStart(2, '0') + ':' +
                    String(total % 60).padStart(2, '0');
                return true;
            }

            function seekForFrame() {
                if (sought) { return; }
                sought = true;
                try {
                    video.currentTime = Math.min(1, Math.max(0, video.duration - 0.1));
                } catch (e) {
                    clearTimeout(timer);
                    finish();
                }
            }

            video.onloadedmetadata = function () {
                if (readDuration()) {
                    seekForFrame();
                    return;
                }
                // Streamed/fragmented containers report Infinity until the
                // browser is forced to seek to the end.
                try {
                    video.currentTime = 1e101;
                } catch (e) {
                    seekForFrame();
                }
            };

            video.ondurationchange = function () {
                if (readDuration()) {
                    seekForFrame();
                }
            };

            video.onseeked = function () {
                // Ignore the throwaway seek used to resolve an Infinity
                // duration; only the deliberate poster seek grabs a frame.
                if (!sought) { return; }

                try {
                    var canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function (blob) {
                        result.thumbnailBlob = blob;
                        clearTimeout(timer);
                        finish();
                    }, 'image/jpeg', 0.85);
                } catch (e) {
                    clearTimeout(timer);
                    finish();
                }
            };

            video.src = url;
        });
    }

    /** Same elements and behaviour as each blade's own showProgress(). */
    function paintProgress(pct) {
        var wrap = document.getElementById('upload-progress');
        var bar = document.getElementById('upload-bar');
        var label = document.getElementById('upload-percent');

        if (wrap) { wrap.style.display = ''; }
        if (bar) {
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', pct);
        }
        if (label) { label.textContent = pct; }
    }

    function hiddenField(form, name, value) {
        var input = form.querySelector('input[type="hidden"][name="' + name + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }
        input.value = value;
    }

    /**
     * Sits in front of the form's existing submit handler. When the admin
     * picked a video we hold that handler back, push the bytes to S3, then
     * re-submit so the original handler posts a small form carrying
     * `media_key`. Anything that goes wrong just releases the original
     * handler with the file still attached — i.e. the pre-existing flow.
     */
    function initDirectVideoUpload(formSelector) {
        var form = document.querySelector(formSelector);
        if (!form) { return; }

        $(form).on('submit', function (e) {
            if (form.getAttribute('data-direct-upload') === 'done') {
                return; // second pass: let the original handler run
            }

            var input = form.querySelector('input[name="media"]');
            var file = input && input.files && input.files[0];
            if (!file || file.size > MAX_BYTES || !window.fetch || !window.Promise) {
                return; // nothing to offload, or no support: legacy path
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            var thumbnailInput = form.querySelector('input[name="thumbnail"]');
            var hasThumbnail = !!(thumbnailInput && thumbnailInput.files && thumbnailInput.files.length);

            function release() {
                form.setAttribute('data-direct-upload', 'done');
                $(form).trigger('submit');
            }

            directVideoUpload(file, { onProgress: paintProgress }).then(function (key) {
                if (!key) {
                    release(); // uploader opted out; post the file as before
                    return;
                }

                return probeVideoLocally(file).then(function (probe) {
                    hiddenField(form, 'media_key', key);
                    hiddenField(form, 'media_duration', probe.duration || '');

                    // A poster frame is a nicety: never let it break the save.
                    if (!hasThumbnail && probe.thumbnailBlob && thumbnailInput && window.DataTransfer) {
                        try {
                            var dt = new DataTransfer();
                            dt.items.add(new File([probe.thumbnailBlob], 'poster.jpg', { type: 'image/jpeg' }));
                            thumbnailInput.files = dt.files;
                        } catch (thumbErr) {
                            console.warn('Poster frame skipped:', thumbErr);
                        }
                    }

                    input.value = ''; // the bytes are already on S3
                    release();
                });
            }).catch(function (err) {
                // Never leave a half-applied state behind: the original
                // handler must post exactly what it would have posted before.
                console.error('Direct upload failed, falling back to form post:', err);
                hiddenField(form, 'media_key', '');
                hiddenField(form, 'media_duration', '');
                release();
            });
        });
    }

    window.directVideoUpload = directVideoUpload;
    window.probeVideoLocally = probeVideoLocally;
    window.initDirectVideoUpload = initDirectVideoUpload;
    window.directVideoUploadMaxBytes = MAX_BYTES;
})(window);
