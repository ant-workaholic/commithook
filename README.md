# CommitHOOKs

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/andkirby/commithook?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
The main purpose of this project is checking coding standards.

[![Travis CI](https://travis-ci.org/andkirby/commithook.svg?branch=develop)](https://travis-ci.org/andkirby/commithook)
Travis Continuous Integration status.

#### Latest release: `v2.0.0-beta.20`

### Install last version

Due to reason the package requires one package which is not stable, please use this configuration for your composer.json:
```
{
  "minimum-stability": "dev",
  "prefer-stable": true
}
```
Now install the package.
```shell
$ composer global require andkirby/commithook:~2.0@beta
```
## Main documentation
- [GIT integration: hook files installation](doc/hooks-installation.md)
- [Configuration Wizard](doc/example-wizard.md)
- [Commit message format](doc/commit-msg.md)
- [Manage code validation/protect code](doc/exclude-code-validation.md)

## Tips & tricks
### Redundant gaps in code
You may quickly find gaps in your code by regular expression:
```
(\n\s*\n\s*\})|(\n\s*\n\s*\n)|(\{\n\s*\n)
```
Just use it in your IDE.

## OS environment
Tested on Windows in GIT Bash. Feel free [to create](../../issues/new "Add a new issue") your faced issue.

[Release notes](doc/release-notes.md)
