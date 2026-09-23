<div style="text-align: center;">
  <img style="width: 128px;" src="assets/img/omdb-512x512.png" alt="OMDB logo">
</div>

# OMDB

- PHP version: 8.1
- Needs [Composer](https://getcomposer.org/) installed to run tests and linting
- Recommended to join the [Discord](https://discord.gg/PWVGrQRq2w) if you're contributing

## Format/Linting

`.editorconfig` for any editor with [EditorConfig](https://editorconfig.org/) support

**VS Code users:** `.vscode/extensions.json` and `.vscode/settings.json` are available

Setup once after cloning:
```shell
composer install
```

- `composer lint` for linting
- `composer fix-changed` to format the files your branch changed
