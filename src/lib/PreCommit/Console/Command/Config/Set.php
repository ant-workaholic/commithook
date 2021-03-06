<?php
/**
 * @license https://raw.githubusercontent.com/andkirby/commithook/master/LICENSE.md
 */
namespace PreCommit\Console\Command\Config;

use PreCommit\Console\Command\AbstractCommand;
use PreCommit\Console\Exception;
use PreCommit\Console\Helper;
use Rikby\Crypter\Crypter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * CommitHooks command tester
 *
 * It will test all modified files
 *
 * @package PreCommit\Command
 */
class Set extends AbstractCommand
{
    /**
     * Shell exit code when the same configuration already defined
     */
    const SHELL_CODE_CONF_DEFINED = 10;
    /**
     * Shell exit code for deprecated way
     */
    const SHELL_CODE_COMMAND_DEPRECATED = 11;

    /**#@+
     * Option scopes
     *
     * project-self: ~/.commithook/projects/PROJECT_NAME/commithook.xml
     * project:      PROJECT_DIR/.commithook.xml
     * global:       ~/.commithook/commithook.xml
     */
    const OPTION_SCOPE_GLOBAL       = 'global';
    const OPTION_SCOPE_PROJECT      = 'project';
    const OPTION_SCOPE_PROJECT_SELF = 'project-self';
    /**#@-*/

    /**
     * Tracker type XML path
     */
    const XPATH_TRACKER_TYPE = 'tracker/type';

    /**
     * Scope options
     *
     * A scope is associated with a particular configuration file.
     *
     * @var array
     */
    protected $scopeOptions
        = [
            1 => self::OPTION_SCOPE_GLOBAL,
            2 => self::OPTION_SCOPE_PROJECT,
            3 => self::OPTION_SCOPE_PROJECT_SELF,
        ];

    /**
     * Tracker connection option names
     *
     * @var array
     */
    protected $trackerConnectionOptions
        = [
            'tracker',
            'url',
            'username',
            'password',
        ];

    /**
     * Issues tracker type
     *
     * @var string
     */
    protected $trackerType;

    /**
     * Update status
     *
     * It will true if some file updated
     *
     * @var bool
     */
    protected $updated = false;

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if ($this->checkDeprecatedKey()) {
            return self::SHELL_CODE_COMMAND_DEPRECATED;
        }

        return $this->processValue();
    }

    /**
     * {@inheritdoc}
     */
    public function setApplication(Application $application = null)
    {
        parent::setApplication($application);
        $this->getHelperSet()->set(new Helper\ConfigHelper());
        $this->getHelperSet()->set(new Helper\Config\SetHelper());
        $this->getHelperSet()->set(new Helper\Config\WriterHelper());
        $this->getHelperSet()->set(new Helper\ClearCacheHelper());
        $this->getHelperSet()->set(new Helper\Config\FileFilterHelper());
    }

    /**
     * Show notifications about deprecated commands
     *
     * @return bool
     */
    protected function checkDeprecatedKey()
    {
        if ('wizard' == $this->getKey()) {
            $this->output->writeln('This command is deprecated. Please use');
            $this->output->writeln('');
            $this->output->writeln('    tracker:wizard');
            $this->output->writeln('');

            return true;
        }
        if ('task' == $this->getKey()) {
            $this->output->writeln('This command is deprecated. Please use');
            $this->output->writeln('');
            $this->output->writeln('    tracker:task NUMBER');
            $this->output->writeln('');

            return true;
        }
        if ('exclude-path' == $this->getKey() || 'skip-path' == $this->getKey()
            || 'exclude-file' == $this->getKey() || 'skip-file' == $this->getKey()
        ) {
            $this->output->writeln('This command is deprecated. Please use');
            $this->output->writeln('');
            $this->output->writeln('    files:skip YOUR_PATH');
            $this->output->writeln('');

            return true;
        }
        if ('protected-path' == $this->getKey() || 'protected-file' == $this->getKey()) {
            $this->output->writeln('This command is deprecated. Please use');
            $this->output->writeln('');
            $this->output->writeln('    files:protect YOUR_PATH');
            $this->output->writeln('');

            return true;
        }
        if ('allow-path' == $this->getKey() || 'allow-file' == $this->getKey()) {
            $this->output->writeln('This command is deprecated. Please use');
            $this->output->writeln('');
            $this->output->writeln('    files:allow YOUR_PATH');
            $this->output->writeln('');

            return true;
        }

        return false;
    }

    /**
     * Get key config name
     *
     * @return mixed
     */
    protected function getKey()
    {
        return $this->input->getArgument('key');
    }

    /**
     * Process pair key-value
     *
     * @return int
     */
    protected function processValue()
    {
        if (!$this->getKey()) {
            //show help if key is not defined
            $this->io->writeln($this->getProcessedHelp());

            return 0;
        }

        if (!$this->shouldWriteValue()) {
            $this->showValue();

            return 0;
        }

        return $this->processWriteValue();
    }

    /**
     * Process writing value
     *
     * @return int
     */
    protected function processWriteValue()
    {
        /**
         * Writing mode
         */
        $this->writePredefinedOptions();
        $this->writeKeyValueOption();

        if ($this->updated) {
            if ($this->isVerbose()) {
                $this->output->writeln(
                    'Configuration updated.'
                );
            }
        } else {
            if ($this->isVerbose()) {
                $this->output->writeln(
                    'Configuration already defined.'
                );
            }

            return self::SHELL_CODE_CONF_DEFINED;
        }

        return 0;
    }

    /**
     * Show value in output
     *
     * @return $this
     */
    protected function showValue()
    {
        $xpath = $this->getArgumentXpath();
        $value = $this->getShowValue($xpath);
        if ($value && $value !== trim($value)) {
            $value = "\"$value\"";
        }
        $value && $this->io->writeln($value);

        return $this;
    }

    /**
     * Get value for config
     *
     * @return null|string
     */
    protected function getValue()
    {
        return $this->input->getArgument('value');
    }

    /**
     * Get argument XPath
     *
     * @return string
     * @throws Exception
     */
    protected function getArgumentXpath()
    {
        return $this->hasXpathOption()
            ? $this->getKey()
            : $this->getXpath($this->getKey());
    }

    /**
     * Check if name option is XML path
     *
     * @return bool
     */
    protected function hasXpathOption()
    {
        return $this->input->hasParameterOption('--xpath');
    }

    /**
     * Get XML path by name
     *
     * @param string $name
     * @return string
     * @throws Exception
     */
    protected function getXpath($name)
    {
        if (!$name) {
            throw new Exception('Empty config name.');
        }
        $helperConfigFile = $this->getHelperSet()->get('commithook_config_file');
        switch ($name) {
            case 'password':
            case 'username':
            case 'project':
            case 'url':
                $name = 'tracker/'.$this->getTrackerType().'/'.$name;
                break;

            case 'tracker':
                $name = self::XPATH_TRACKER_TYPE;
                break;

            case 'skip':
            case 'allow':
            case 'protect':
                $name = 'validators/FileFilter/filter/'.$name.'/path';
                if ($this->shouldWriteValue()) {
                    $name = $name.'/'.$helperConfigFile->path2XmlNode($this->getValue());
                }
                break;

            case 'task':
                $name = 'tracker/'.$this->getTrackerType().'/active_task';
                break;

            default:
                throw new Exception("Unknown config name '$name'.");
        }

        return $name;
    }

    /**
     * Get issues tracker type
     *
     * @return string
     */
    protected function getTrackerType()
    {
        if ($this->trackerType) {
            return $this->trackerType;
        }
        $this->trackerType = $this->getConfig()->getNode(self::XPATH_TRACKER_TYPE);
        if (!$this->trackerType) {
            new Exception('Tracker type is not set. Please use command: commithook config --tracker [TRACKER]');
        }

        return $this->trackerType;
    }

    /**
     * Check if it should write value
     *
     * @return null|string
     */
    protected function shouldWriteValue()
    {
        return null !== $this->getValue() || $this->shouldSet() || $this->shouldUnset();
    }

    /**
     * Check if should remove value
     *
     * @return bool
     */
    protected function shouldUnset()
    {
        return $this->input->hasParameterOption(['--unset', '-u']);
    }

    /**
     * Check if should remove value
     *
     * @return bool
     */
    protected function shouldSet()
    {
        return $this->input->hasParameterOption(['--set', '-t']);
    }

    /**
     * Get show value
     *
     * @param string $xpath
     * @return null|string
     */
    protected function getShowValue($xpath)
    {
        return $this->getXpathValue($xpath);
    }

    /**
     * Get value by xpath
     *
     * @param string $xpath
     * @return null|string
     */
    protected function getXpathValue($xpath)
    {
        return false === strpos($xpath, 'password') ?
            $this->getConfig()->getNode($xpath) : null;
    }

    /**
     * Write predefined options
     *
     * @param bool $readAll
     * @throws Exception
     */
    protected function writePredefinedOptions($readAll = false)
    {
        foreach ($this->trackerConnectionOptions as $name) {
            $value = $this->input->getOption($name);
            if (!$readAll && null === $value) {
                continue;
            }
            $xpath = $this->hasXpathOption() ? $name : $this->getXpath($name);
            $scope = $this->getScope($xpath);
            $this->writeConfig($xpath, $scope, $value);
        }
    }

    /**
     * Get XML path input options
     *
     * @param string        $xpath
     * @param Question|null $question
     * @return int
     */
    protected function getScope($xpath, $question = null)
    {
        $type = null;
        if (self::XPATH_TRACKER_TYPE !== $xpath) {
            $type = $this->getTrackerType();
        }

        $default = $this->getDefaultScope($xpath, $type);

        if ($this->isFirmScope($xpath, $type) || $this->useDefaultScopeByDefault()) {
            return $default;
        }

        $scope   = $this->getScopeOption();
        $options = $this->getAvailableScopeOptions($xpath, $type);

        if ($scope && in_array($scope, $options)) {
            return $scope;
        }

        return $this->io->askQuestion(
            $question
                ?: $this->getSimpleQuestion()
                ->getQuestion("Set config scope ($xpath)", $default, $options)
        );
    }

    /**
     * Use default scope by default
     *
     * Ie do not ask question
     *
     * @return bool
     */
    protected function useDefaultScopeByDefault()
    {
        return false;
    }

    /**
     * Get default scope ID
     *
     * @param string $xpath
     * @param string $type
     * @return int
     */
    protected function getDefaultScope($xpath, $type)
    {
        switch ($xpath) {
            case 'tracker/'.$type.'/active_task':
                $default = self::OPTION_SCOPE_PROJECT_SELF;
                break;
            case 'tracker/'.$type.'/project':
                $default = self::OPTION_SCOPE_PROJECT;
                break;

            case self::XPATH_TRACKER_TYPE:
                $default = self::OPTION_SCOPE_PROJECT;
                break;

            case 'tracker/'.$type.'/url':
                $default = self::OPTION_SCOPE_GLOBAL;
                break;

            case 'tracker/'.$type.'/username':
            case 'tracker/'.$type.'/password':
                $default = self::OPTION_SCOPE_GLOBAL;
                break;

            default:
                $default = self::OPTION_SCOPE_PROJECT_SELF;
                break;
        }

        return $default;
    }

    /**
     * Check if firm scope
     *
     * In this case default scope must be used
     *
     * @param string $xpath
     * @param string $type
     * @return bool
     */
    protected function isFirmScope($xpath, $type)
    {
        $firm = false;
        switch ($xpath) {
            case 'tracker/'.$type.'/active_task':
            case 'tracker/'.$type.'/project':
            case 'tracker/type':
                $firm = true;
                break;
            //no default
        }

        return $firm;
    }

    /**
     * Get scope option
     *
     * @return null|string
     */
    protected function getScopeOption()
    {
        if ($this->input->getOption(self::OPTION_SCOPE_GLOBAL)) {
            return self::OPTION_SCOPE_GLOBAL;
        }
        if ($this->input->getOption(self::OPTION_SCOPE_PROJECT)) {
            return self::OPTION_SCOPE_PROJECT;
        }
        if ($this->input->getOption(self::OPTION_SCOPE_PROJECT_SELF)) {
            return self::OPTION_SCOPE_PROJECT_SELF;
        }

        return null;
    }

    /**
     * Get available scope options
     *
     * @param string $xpath
     * @param string $type
     * @return array
     */
    protected function getAvailableScopeOptions($xpath, $type)
    {
        $options = $this->scopeOptions;
        switch ($xpath) {
            case 'tracker/'.$type.'/username':
            case 'tracker/'.$type.'/password':
                unset($options[2]);
                break;
            //no default
        }

        return $options;
    }

    /**
     * Write config
     *
     * @param string $xpath
     * @param string $scope
     * @param string $value
     * @return $this
     * @throws Exception
     */
    protected function writeConfig($xpath, $scope, $value)
    {
        //encrypt password TODO refactor this block
        if ('password' === $xpath
            || strpos($xpath, '/password')
        ) {
            $value = $this->encrypt($value);
        }

        $result = $this->getConfigHelper()->writeValue(
            $this->getConfigFile($scope, $xpath),
            $xpath,
            ($this->shouldUnset() ? null : $value)
        );
        if (self::XPATH_TRACKER_TYPE === $xpath) {
            $this->trackerType = $value;
        }

        $this->updated = $result ?: $this->updated;

        return $this;
    }

    /**
     * Encrypt password
     *
     * @param string $password
     * @return string
     */
    protected function encrypt($password)
    {
        $crypter = new Crypter();

        return $crypter->encrypt($password);
    }

    /**
     * Get config helper
     *
     * @return Helper\ConfigHelper
     */
    protected function getConfigHelper()
    {
        return $this->getHelperSet()->get('commithook_config');
    }

    /**
     * Get config file related to scope
     *
     * @param string $scope
     * @param string $xpath
     * @return null|string
     * @throws \PreCommit\Exception
     */
    protected function getConfigFile($scope, $xpath)
    {
        if (self::OPTION_SCOPE_GLOBAL == $scope) {
            return $this->getConfig()->getConfigFile('userprofile');
        } elseif (self::OPTION_SCOPE_PROJECT == $scope) {
            return $this->getConfigProjectFileByXpath($xpath)
                ?: $this->getProjectConfigFile();
        } elseif (self::OPTION_SCOPE_PROJECT_SELF == $scope) {
            return $this->getConfig()->getConfigFile('project_local');
        }

        throw new \PreCommit\Exception("Unknown scope '$scope'.");
    }

    /**
     * Get config file by xpath for project scope
     *
     * It will get validator/filter name and make file path in PROJECT_DIR/.commithook/
     *
     * @param string $xpath
     * @return null|string
     * @throws Exception
     */
    protected function getConfigProjectFileByXpath($xpath)
    {
        if (!preg_match('~^validators/([A-Z][A-z0-9_-]+)~', $xpath, $matches)
            && !preg_match('~^filters/([A-Z][A-z0-9_-]+)~', $xpath, $matches)
            && !preg_match('~/filters/([A-Z][A-z0-9_-]+)~', $xpath, $matches)
            && !preg_match('~^hooks/pre-commit/filetype/[A-z_-]+/[A-z_-]+/([A-Z][A-z0-9_-]+)~', $xpath, $matches)
            && !preg_match('~^hooks/pre-commit/ignore/validator/code/([A-Z][A-z0-9_-]+)~', $xpath, $matches)
        ) {
            return null;
        }

        $path = $this->getProjectConfigFile();

        if (!$path) {
            throw new Exception('Path cannot be empty.');
        }

        return dirname($path).'/.commithook/'.$matches[1].'.xml';
    }

    /**
     * Get project config file
     *
     * @return null|string
     */
    protected function getProjectConfigFile()
    {
        if (is_file($this->getConfig()->getConfigFile('project_old'))) {
            return $this->getConfig()->getConfigFile('project_old');
        }

        return $this->getConfig()->getConfigFile('project');
    }

    /**
     * Write key-value option
     *
     * @return $this
     * @throws Exception
     */
    protected function writeKeyValueOption()
    {
        if (!$this->getKey()) {
            /**
             * Ignore if nothing to write
             */
            return $this;
        }

        $xpath = $this->getArgumentXpath();

        $value = $this->fetchValue($xpath);
        $scope = $this->getScope($xpath);

        $this->writeConfig($xpath, $scope, $value);

        return $this;
    }

    /**
     * Get value
     *
     * @param string $xpath
     * @return string
     */
    protected function fetchValue($xpath)
    {
        if ($this->shouldUnset()) {
            return '';
        }

        if (null === $this->getValue() && $this->shouldSet()) {
            return $this->askValue($xpath);
        }

        return $this->getValue();
    }

    /**
     * Ask value
     *
     * @param string $xpath
     * @return string
     */
    protected function askValue($xpath)
    {
        $question = $this->getSimpleQuestion()->getQuestion(
            "Set value for XPath '$xpath'",
            $this->getXpathValue($xpath)
        );

        /**
         * Ask value without showing input for passwords
         */
        if (false !== strpos($xpath, 'password')) {
            $question->setHidden(true);
            $question->setHiddenFallback(true);
        }

        return $this->io->askQuestion($question);
    }

    /**
     * Get XML path input options
     *
     * @param string $xpath
     * @return array
     */
    protected function getXpathOptions($xpath)
    {
        switch ($xpath) {
            case self::XPATH_TRACKER_TYPE:
                $values = array_values($this->getConfig()->getNodeArray('tracker/available_type'));
                break;

            default:
                return [];
        }
        $keys = array_keys(array_fill(1, count($values), 1));

        return array_combine($keys, $values);
    }

    /**
     * Get credentials scope
     *
     * @return int
     */
    protected function getCredentialsScope()
    {
        $scopeOptions = $this->scopeOptions;
        unset($scopeOptions[1]);

        return $this->getScope(
            $this->getXpath('username'),
            $this->getSimpleQuestion()->getQuestion(
                'Set config scope credentials',
                1,
                $scopeOptions
            )
        );
    }

    /**
     * Init default helpers
     *
     * @return $this
     */
    protected function configureCommand()
    {
        $this->setName('config');

        //@startSkipCommitHooks
        $help
            = <<<HELP
This command can set CommitHook configuration.
Allowed predefined keys:
Tracker:
    tracker
        Issue tracker type code (jira, github etc).
    url
        Issue tracker API URL.
    username
        Username for issue tracker authorization.
    password
        Password for issue tracker authorization.
    project
        Project key in selected issue tracker.
HELP;
        //@finishSkipCommitHooks

        $this->setHelp($help);
        $this->setDescription(
            'This command can set CommitHook configuration.'
        );

        return $this;
    }

    /**
     * Init input definitions
     *
     * @return $this
     */
    protected function configureInput()
    {
        parent::configureInput();
        $this->addArgument('key', InputArgument::REQUIRED);
        $this->addArgument('value', InputArgument::OPTIONAL);

        $this->setUnsetOption();
        $this->setSetOption();

        /**
         * When this parameter set key must be an XML path
         */
        $this->addOption(
            'xpath',
            '-x',
            InputOption::VALUE_NONE,
            'XPath mode. "key" parameter will be considered as an XML path.'
        );

        $this->setScopeOptions();

        foreach ($this->trackerConnectionOptions as $name) {
            $this->addOption(
                $name,
                null,
                InputOption::VALUE_OPTIONAL,
                "Tracker connection '$name' option."
            );
        }

        return $this;
    }

    /**
     * Set unset option
     *
     * @return $this
     */
    protected function setUnsetOption()
    {
        $this->addOption('unset', 'u', InputOption::VALUE_NONE, 'Remove exist value.');

        return $this;
    }

    /**
     * Set unset option
     *
     * @return $this
     */
    protected function setSetOption()
    {
        $this->addOption('set', 't', InputOption::VALUE_NONE, 'Set value in dialog.');

        return $this;
    }

    /**
     * Set scope options
     *
     * @return $this
     */
    protected function setScopeOptions()
    {
        /**
         * Scope options
         */
        $this->addOption(
            'global',
            '-g',
            InputOption::VALUE_NONE,
            'Save config in global configuration file.'
        );
        $this->addOption(
            'project-self',
            '-s',
            InputOption::VALUE_NONE,
            'Save config in project private(!) configuration file. '
            .'~/.commithook/projects/PROJECT_DIR_NAME/commithook.xml'
        );
        $this->addOption(
            'project',
            '-P',
            InputOption::VALUE_NONE,
            'Save config in project configuration file. PROJECT_DIR/.commithook.xml'
        );

        return $this;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return mixed
     */
    protected function setValue($value)
    {
        return $this->input->setArgument('value', $value);
    }
}
