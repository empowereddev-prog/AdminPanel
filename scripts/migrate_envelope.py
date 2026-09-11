#!/usr/bin/env python3
"""Convert hand-rolled response()->json envelopes to ApiResponse calls.

Phase 2 of the mobile API remediation - see docs/API_REMEDIATION.md.

It converts ONLY the canonical {status, message, data} shape with a literal
true/false status, and refuses everything else by design: non-canonical key sets
carry v1 keys that need a deliberate $legacy/$extra decision, and 'data' => null
would become {} - a type change for the shipped app. Refusals are printed;
handle them by hand.

    python3 scripts/migrate_envelope.py <file>            # dry run
    python3 scripts/migrate_envelope.py <file> --apply

Always add the `use App\\Support\\ApiResponse;` import yourself, and run the
snapshot gate after each pass:

    php vendor/bin/phpunit tests/Feature/Api/ResponseContractSnapshotTest.php
"""
import re, io, sys

def split_top(body):
    """Split an array body on top-level commas."""
    parts, depth, buf, i = [], 0, [], 0
    instr = None
    while i < len(body):
        ch = body[i]
        if instr:
            if ch == '\\':
                buf.append(ch); i += 1
                if i < len(body): buf.append(body[i]); i += 1
                continue
            if ch == instr: instr = None
            buf.append(ch); i += 1; continue
        if ch in "'\"":
            instr = ch; buf.append(ch); i += 1; continue
        if ch in '([{': depth += 1
        elif ch in ')]}': depth -= 1
        if ch == ',' and depth == 0:
            parts.append(''.join(buf)); buf = []; i += 1; continue
        buf.append(ch); i += 1
    if ''.join(buf).strip():
        parts.append(''.join(buf))
    return parts

def transform(path, dry=True):
    src = io.open(path, encoding='utf-8').read()
    out, i, changed, skipped = [], 0, 0, []
    while True:
        j = src.find("response()->json([", i)
        if j < 0:
            out.append(src[i:]); break

        # skip commented-out occurrences
        line_start = src.rfind('\n', 0, j) + 1
        if src[line_start:j].lstrip().startswith('//'):
            out.append(src[i:j + 1]); i = j + 1; continue

        k = j + len("response()->json([")
        depth = 1
        while k < len(src) and depth > 0:
            if src[k] == '[': depth += 1
            elif src[k] == ']': depth -= 1
            k += 1
        body = src[j + len("response()->json(["):k - 1]

        m = re.match(r"\s*,\s*(\d{3})\s*\)", src[k:])
        m2 = re.match(r"\s*\)", src[k:])
        if m:
            code, end = m.group(1), k + m.end()
        elif m2:
            code, end = '200', k + m2.end()
        else:
            # Unparseable tail - e.g. a variable status code rather than a
            # literal. This used to fall through silently, which is the one
            # thing a refusal-based tool must never do: the block vanished
            # from both the converted count and the skip list.
            skipped.append((j, "could not parse the status-code argument"))
            out.append(src[i:j + 1]); i = j + 1; continue

        pairs = {}
        order = []
        ok = True
        for part in split_top(body):
            # Both quote styles: NotificationController writes "status" => ...,
            # and a single-quote-only pattern reported those blocks as keys=[],
            # which reads like an unparseable block rather than a normal one.
            pm = re.match(r"\s*['\"]([a-z_0-9]+)['\"]\s*=>\s*(.*)$", part, re.S)
            if not pm:
                ok = False; break
            pairs[pm.group(1)] = pm.group(2).strip()
            order.append(pm.group(1))

        keys = set(pairs)
        indent = ' ' * (j - line_start)

        if ok and keys == {'status', 'message', 'data'} and pairs['status'] in ('true', 'false'):
            data = pairs['data']
            if data.strip() in ('null',):
                skipped.append((j, "data => null would become {}")); out.append(src[i:j+1]); i = j+1; continue
            if pairs['status'] == 'true':
                repl = f"ApiResponse::success({data}, {pairs['message']}, {code})"
            else:
                repl = f"ApiResponse::error({pairs['message']}, {code}, null, {data})"
            out.append(src[i:j]); out.append(repl)
            i = end; changed += 1; continue

        skipped.append((j, f"keys={sorted(keys)} status={pairs.get('status','?')}"))
        out.append(src[i:j + 1]); i = j + 1

    result = ''.join(out)

    # Insert the import ourselves. Doing this by hand cost two 500s: the file
    # still lints clean without it (php -l does not resolve classes), so the
    # only thing that caught it was the snapshot gate. Same-namespace
    # controllers have no `use ...\Controller;` line to anchor to, which is
    # exactly where the manual step silently did nothing.
    if changed and 'use App\\Support\\ApiResponse;' not in result:
        m = re.search(r'^use .+;$', result, re.M)
        if m:
            result = result[:m.start()] + "use App\\Support\\ApiResponse;\n" + result[m.start():]
        else:
            skipped.append((0, "COULD NOT INSERT the ApiResponse import - add it by hand"))

    if not dry:
        io.open(path, 'w', encoding='utf-8').write(result)
    return changed, skipped

path = sys.argv[1]
dry = '--apply' not in sys.argv
src_text = io.open(path, encoding='utf-8').read()
changed, skipped = transform(path, dry)
print(("DRY RUN: " if dry else "APPLIED: ") + f"{changed} blocks converted, {len(skipped)} left for manual review")
for off, why in skipped:
    print("   skip: line %d: %s" % (src_text[:off].count('\n') + 1, why))
