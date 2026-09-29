@php
    $selectedIds = array_map('strval', (array) ($selectedIds ?? []));
    $vpSelectedCount = $videos->whereIn('id', $selectedIds)->count();
@endphp

<div class="vp-picker" data-vp-picker>
    <div class="vp-head">
        <label class="vp-label" for="vp-search">Select Video(s)</label>
        <span class="vp-count" data-vp-count>{{ $vpSelectedCount }} selected</span>
    </div>

    @if ($videos->isEmpty())
        <p class="vp-empty-catalogue">No active mood videos are available. Add one under Video More first.</p>
    @else
        <div class="vp-chips" data-vp-chips></div>

        <div class="vp-toolbar">
            <input type="text" id="vp-search" class="form-control vp-search" data-vp-search
                placeholder="Search videos by title…" autocomplete="off">
            <button type="button" class="btn btn-sm btn-outline-primary vp-btn" data-vp-select-all>Select all shown</button>
            <button type="button" class="btn btn-sm btn-outline-secondary vp-btn" data-vp-clear-all>Clear all</button>
        </div>

        <div class="vp-list" data-vp-list>
            @foreach ($videos as $video)
                <label class="vp-item" data-vp-title="{{ Str::lower($video->title) }}">
                    <input type="checkbox" name="video[]" value="{{ $video->id }}"
                        @checked(in_array((string) $video->id, $selectedIds, true))>
                    <span class="vp-item-title">{{ $video->title }}</span>
                </label>
            @endforeach
            <p class="vp-no-match" data-vp-empty hidden>No videos match your search.</p>
        </div>
    @endif

    @error('video')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

@once
    @push('styles')
        <style>
            .vp-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 8px;
            }

            .vp-label {
                margin: 0;
                font-weight: 500;
            }

            .vp-count {
                background: #eef1f7;
                color: #3b4256;
                border-radius: 999px;
                padding: 2px 12px;
                font-size: 12px;
                white-space: nowrap;
            }

            .vp-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 10px;
            }

            .vp-chips:empty {
                display: none;
            }

            .vp-chip {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #e8f0fe;
                color: #1c3f7c;
                border-radius: 999px;
                padding: 4px 6px 4px 12px;
                font-size: 13px;
                max-width: 100%;
            }

            .vp-chip-label {
                overflow-wrap: anywhere;
            }

            .vp-chip-remove {
                border: 0;
                background: rgba(28, 63, 124, .12);
                color: inherit;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                line-height: 1;
                font-size: 13px;
                cursor: pointer;
                flex: 0 0 auto;
            }

            .vp-chip-remove:hover {
                background: rgba(28, 63, 124, .28);
            }

            .vp-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                margin-bottom: 10px;
            }

            .vp-search {
                flex: 1 1 260px;
                min-width: 180px;
            }

            .vp-btn:disabled {
                opacity: .5;
                cursor: not-allowed;
            }

            .vp-list {
                border: 1px solid #dfe3ec;
                border-radius: 6px;
                max-height: 320px;
                overflow-y: auto;
                padding: 4px 0;
            }

            .vp-item {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                margin: 0;
                padding: 8px 14px;
                cursor: pointer;
                font-weight: 400;
            }

            .vp-item:hover {
                background: #f5f7fb;
            }

            .vp-item input[type="checkbox"] {
                margin-top: 3px;
                flex: 0 0 auto;
            }

            .vp-item-title {
                overflow-wrap: anywhere;
            }

            .vp-item.vp-hidden {
                display: none;
            }

            .vp-no-match,
            .vp-empty-catalogue {
                color: #8a90a2;
                font-size: 13px;
                margin: 0;
                padding: 12px 14px;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function ($) {
                'use strict';

                function boxes($picker) {
                    return $picker.find('[data-vp-list] input[type="checkbox"]');
                }

                function render($picker) {
                    var $all = boxes($picker);
                    var $checked = $all.filter(':checked');
                    var $chips = $picker.find('[data-vp-chips]').empty();

                    $checked.each(function () {
                        var title = $(this).closest('.vp-item').find('.vp-item-title').text();
                        var $chip = $('<span class="vp-chip"></span>');
                        $('<span class="vp-chip-label"></span>').text(title).appendTo($chip);
                        $('<button type="button" class="vp-chip-remove" aria-label="Remove"></button>')
                            .text('✕')
                            .attr('data-vp-chip-remove', this.value)
                            .appendTo($chip);
                        $chips.append($chip);
                    });

                    $picker.find('[data-vp-count]').text($checked.length + ' selected');

                    var visibleUnchecked = $all.filter(function () {
                        return !this.checked && !$(this).closest('.vp-item').hasClass('vp-hidden');
                    }).length;

                    $picker.find('[data-vp-select-all]').prop('disabled', visibleUnchecked === 0);
                    $picker.find('[data-vp-clear-all]')
                        .prop('disabled', $checked.length === 0)
                        .text($checked.length ? 'Clear all (' + $checked.length + ')' : 'Clear all');
                }

                function filter($picker) {
                    var term = $.trim(($picker.find('[data-vp-search]').val() || '')).toLowerCase();
                    var visible = 0;

                    $picker.find('.vp-item').each(function () {
                        var match = term === '' || ($(this).data('vp-title') + '').indexOf(term) !== -1;
                        $(this).toggleClass('vp-hidden', !match);
                        if (match) {
                            visible++;
                        }
                    });

                    $picker.find('[data-vp-empty]').prop('hidden', visible !== 0);
                    render($picker);
                }

                $(document)
                    .on('change', '[data-vp-picker] [data-vp-list] input[type="checkbox"]', function () {
                        render($(this).closest('[data-vp-picker]'));
                    })
                    .on('click', '[data-vp-picker] [data-vp-chip-remove]', function () {
                        var $picker = $(this).closest('[data-vp-picker]');
                        boxes($picker).filter('[value="' + $(this).attr('data-vp-chip-remove') + '"]')
                            .prop('checked', false);
                        render($picker);
                    })
                    .on('input', '[data-vp-picker] [data-vp-search]', function () {
                        filter($(this).closest('[data-vp-picker]'));
                    })
                    .on('click', '[data-vp-picker] [data-vp-select-all]', function () {
                        var $picker = $(this).closest('[data-vp-picker]');
                        boxes($picker).filter(function () {
                            return !$(this).closest('.vp-item').hasClass('vp-hidden');
                        }).prop('checked', true);
                        render($picker);
                    })
                    .on('click', '[data-vp-picker] [data-vp-clear-all]', function () {
                        var $picker = $(this).closest('[data-vp-picker]');
                        boxes($picker).prop('checked', false);
                        render($picker);
                    });

                $(function () {
                    $('[data-vp-picker]').each(function () {
                        render($(this));
                    });
                });
            })(jQuery);
        </script>
    @endpush
@endonce
