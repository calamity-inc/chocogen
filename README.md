# chocogen

Easily create .nupkg files for your software.

## Installation

If you want the "chocogen" command to be available globally, you can install it via Chocolatey:

```
choco source add -n "Calamity, Inc." -s https://choco.calamity.gg/index.json
choco install chocogen
```

Note that Chocolatey 2.0.0 or above is needed.

## Known issues

- Path is not cleaned up after uninstall of package with PHP script in path. This is a Chocolatey issue. See https://github.com/chocolatey/choco/issues/310.
