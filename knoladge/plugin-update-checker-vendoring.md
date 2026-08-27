# How: vendoring yahnis-elsts/plugin-update-checker for the GitHub updater

## What I did

1. Added `yahnis-elsts/plugin-update-checker` (`^5.4`) to
   `composer.json`'s **production** `require` (not `require-dev` —
   this one actually runs at request time, unlike the PHPCS/PHPUnit
   tooling). `composer update yahnis-elsts/plugin-update-checker`
   resolved and locked v5.7.
2. Copied the installed package (`vendor/yahnis-elsts/plugin-update-checker/`)
   into `inc/lib/plugin-update-checker/` via `rsync -a --delete`, and
   load it at runtime from there (`plugin-update-checker.php`, the
   library's own Composer-independent standalone bootstrap) — never
   through the top-level `vendor/autoload.php`. Reasoning documented
   in full in `features/github-updater.md` and originally in
   `github.md`: generic deploy tooling can silently drop a folder
   literally named `vendor/`.
3. `.gitignore`'s existing `/vendor/` rule is root-anchored (leading
   slash), so it does **not** match `inc/lib/plugin-update-checker/vendor/`
   (the library's own small nested vendor dir, containing Parsedown) —
   that nested copy stays trackable/shippable, same as `github.md`
   describes for the other project.

## What I learned

- **v5.7 has no bare global `PucFactory` class.** `github.md` (from a
  different/older project) shows unqualified `PucFactory::buildUpdateChecker(...)`
  — that only resolves in a file with a `use ...PucFactory;` import.
  In this vendored v5.7 copy, the class lives at
  `YahnisElsts\PluginUpdateChecker\v5p7\PucFactory`, **and** the
  library also ships a version-agnostic facade at
  `YahnisElsts\PluginUpdateChecker\v5\PucFactory` (`Puc/v5/PucFactory.php`
  — a class that conditionally `extends` whichever specific minor
  version was actually loaded). Used the **`v5` facade**, not the
  pinned `v5p7` class directly — it's the library's own documented
  "safe to bundle in multiple plugins/themes without version
  conflicts" entry point, and means a future vendored-copy upgrade
  (e.g. to v5.9) won't require touching `inc/updater.php` at all.
- Verified the class resolves with a standalone `php -r` script that
  just requires the bootstrap file and checks
  `class_exists()`/`method_exists()` — quick way to confirm a vendored
  library loads correctly without needing a full WordPress request.

## Related

[[github-updater]] (features/) — the feature this vendoring supports.
