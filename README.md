# PHP CommitHOOKs

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/andkirby/commithook?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
The main purpose of this project is checking coding standards at first for PHP files.

## Composer Installation

To install Composer you may follow the link https://getcomposer.org/download/. Just go to your PHP directory and follow the instructions therefrom. ([Composer Intro](https://getcomposer.org/doc/00-intro.md))

Install composer package `andkirby/commithook` by command (you may put it into your global vendor directory):
```shell
$ composer require andkirby/commithook
```
### Set up via command line

This feature is available since v1.6.3. _(Simple install command `commithook-install` is available since v1.6.0.)_

#### `commithook install`: Generate files

Before you start please be sure that shell can run files from `vendor/bin` directory ([read more](#set-up-composer-vendorbin-directory)). Or use absolute path `/path/to/vendor/bin/commithook`.

Go to your project root directory and install commithook files:
```shell
$ cd /d/home/my-project
$ commithook install
PHP CommitHook files have been created in 'd:/home/my-project/.git/hooks'.
```
Actually you may skip step of getting in a project directory.
Run `commithook install` anywhere and put path to your project:
```shell
$ cd /d/home
$ commithook install
Please set your root project directory [d:/home]: d:/home/my-project
PHP CommitHook files have been created in 'd:/home/my-project/.git/hooks'.
```
If system couldn't find path to your executable PHP file it will ask about it.

Since PHP 5.4 console should not ask your about PHP binary file. Anyway you may set up path to your PHP binary file.
Also you may set path your project/VCS root.
```shell
$ commithook install --php-binary=d:/s/php/php.exe --project-dir=d:/home/my-project
```
Or short version:
```shell
$ commithook install -pd:/s/php/php.exe -dd:/home/my-project
```

NOTE: Tested on Windows. Feel free [to put](../../issues/new "Add a new issue") your faced issues on [the project issues page](../../issues "Issues").

##### Extra Options
`commithook install` has options.
Please take a look them via command `commithook install -h`.
```shell
$ commithook install -h
[...]
Options:
 --project-dir (-d)    Set path to project (VCS) root directory.
 --hook                Set specific hook file to install.
 --commit-msg          Set 'commit-msg' hook file to install.
 --pre-commit          Set 'pre-commit' hook file to install.
 --overwrite (-w)      Overwrite exist hook files.
 --php-binary (-p)     Set path to PHP binary file.
[...]
```

#### `commithook remove`: Remove Hooks Files
To remove CommitHook files from your project you may use command:
```shell
$ commithook remove
```
Options list the same but without `--overwrite` and `--php-binary`.

#### Set up Composer vendor/bin directory
If you using GitBash or Unix system please be sure that your shell can find files in global vendor directory.
Or try to use absolute path `/path/to/vendor/bin/commithook`.
##### GitBash for Windows
You may don't have `vendor/bin` directory in global `PATH` environment variable.
For GitBash for Windows you can check this out:
```shell
$ echo $PATH | grep "vendor/bin"
```
If you got nothing try to add your `vendor/dir` path to your `~/.bashrc` file:
```shell
echo 'PATH=$PATH":/d/yourpath/to/php/vendor/bin"' >> ~/.bashrc
```
and restart your shell.

## Set up GIT hooks manually

To set up GIT hooks you have to set up your commit-msg and pre-commit files.
If you placed commithook project into the same projects root directory and you 
have just to copy such files from commithook directory into yourproject/.git/hooks.
In other cases please set up them manually.

Also you may use following template to create commit-msg and pre-commit files:
```php
#!/usr/bin/env /your/php
<?php
\$hookName = __FILE__;
require_once '/path/to/commithook/LibHooks/runner.php';
```

## Configuration

### How to use configuration layers

The system loads LibHooks/config.xml file at first.

#### Config caching
In then it will try to load cached file with full merged configuration by path:

- commithook/.cache/md5(version + directory of hookfile).xml

Cache will be invalidated if version was updated.

*NOTE:* In case you changed your local config files your need to clean up cache files. 

#### Config layers
In such case it will merge all files in the XML node "additional_config". There are several default config XML files which will be loaded by default. So default files ordering is presented as this list below:
- commithook/LibHooks/config.xml (base configuration)
- commithook/LibHooks/commithook.xml (contains main part of configuration)
- commithook/LibHooks/commithook-magento.xml (contains configuration for magento projects)
- commithook/commithook-local.xml (it may contain your specific local configuration)
- PROJECT_DIR/commithook.xml (it may contain a project specific configuration which can be shared among your team)
The last one can be added into a project and might be used by all developers. PROJECT_DIR - is your project directory where from CommitHOOK has been run.

# Release notes
- v1.6.5 Added new options command `--php-binary|-b` and `--project-dir|-d`. Improved PHP file validator.
- v1.6.4 Pushed tests to use PSR-4 autoload standard and to namespaces usage. Pushed code to use `bin/runner.php` file. `LibHooks/runner.php` is deprecated. Composer package require at least PHP 5.3.x version.
- v1.6.3 Improved installer. Added CommitHook files remover.
- v1.6.2 (alpha) Implemented application console usage.
- v1.6.1 Fixed dialog message.
- v1.6.0 Added CommitHook files installer.
- v1.5.1 Added composer supporting.
- v1.5.0 Implemented layered configuration loading.
- v1.4.3 Minor fix in the check trailing spaces.
- v1.4.2 [CRITICAL] Fixed running validators from pre-commit processor.
- v1.4.1 Added tests for trailing spaces and trailing line validator. Improved trailing spaces validation.
- v1.4.0 Added checking GIT conflict lines to prevent commits conflicted files.
- v1.3.0 Added checking empty type in PHPDoc tags @param @var
- v1.2.4 Added close symbol on split content. Added checking slashed slash (\\) on split content.
- v1.2.3 Fixed case when operator name used in variable.
- v1.2.2 Bugfix for case:
<?php if ($i != $recommendationsCount-1) echo ","?>
