<?php

/**
 * Class test for PreCommit_Processor
 */
class PreCommit_Validator_TrailingSpaceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Php file for text hooks
     *
     * @var string
     */
    static protected $_fileTest = 'tests/testsuite/PreCommit/_fixture/file-have-trailing-space-and-dont-have-last-empty.php';

    /**
     * Test model
     *
     * @var \PreCommit\Processor\PreCommit
     */
    static protected $_model;

    /**
     * Set up test model
     */
    static public function setUpBeforeClass()
    {
        //init config object
        \PreCommit\Config::getInstance(array('file' => PROJECT_ROOT . '/commithook.xml'));

        $vcsAdapter = self::_getVcsAdapterMock();

        /** @var PreCommit\Processor\PreCommit $processor */
        $processor = PreCommit\Processor::factory('pre-commit', $vcsAdapter);
        $processor->setCodePath(PROJECT_ROOT)
            ->setFiles(array(self::$_fileTest));
        $processor->process();
        self::$_model = $processor;
    }

    /**
     * Get VCS adapter mock
     *
     * @return object
     */
    protected static function _getVcsAdapterMock()
    {
        $vcsAdapter = PHPUnit_Framework_MockObject_Generator::getMock('PreCommit\Vcs\Git');
        $vcsAdapter->expects(self::once())
            ->method('getAffectedFiles')
            ->will(self::returnValue(array()));
        return $vcsAdapter;
    }

    /**
     * Get specific errors list
     *
     * @param string $file
     * @param string $code
     * @param bool $returnLines
     * @return array
     * @throws PHPUnit_Framework_Exception
     */
    protected function _getSpecificErrorsList($file, $code, $returnLines = false)
    {
        $errors = self::$_model->getErrors();
        if (!isset($errors[$file])) {
            throw new PHPUnit_Framework_Exception('Errors for file ' . self::$_fileTest . ' not found.');
        }
        $errors = $errors[$file];

        $this->assertArrayHasKey($code, $errors);
        if (!isset($errors[$code])) {
            throw new PHPUnit_Framework_Exception("Errors for code $code not found.");
        }

        $list = array();
        $key = $returnLines ? 'line' : 'value';
        foreach ($errors[$code] as $item) {
            if ($key == 'value' && isset($item['line'])) {
                $list[$item['line']] = $item[$key];
            } else {
                $list[] = $item[$key];
            }
        }
        return $list;
    }

    /**
     * Test CODE_PHP_OPERATOR_SPACES_MISSED
     */
    public function testExistTrailingSpaces()
    {
        $errors = $this->_getSpecificErrorsList(
            self::$_fileTest,
            \PreCommit\Validator\TrailingSpace::CODE_PHP_REDUNDANT_TRAILING_SPACES
        );

        $expected = array(3);
        $this->assertEquals($expected, $errors);
    }

    /**
     * Test CODE_PHP_OPERATOR_SPACES_MISSED
     */
    public function testNotExistsTrailingLine()
    {
        $errors = $this->_getSpecificErrorsList(
            self::$_fileTest,
            \PreCommit\Validator\TrailingSpace::CODE_PHP_NO_END_TRAILING_LINE
        );

        $this->assertCount(1, $errors);
    }

    /**
     * Test finding trailing space and not exist trailing spaces (full test)
     */
    public function testFindTrailingLineAndNotExistTrailingSpaces()
    {
        $errorCollector = $this->getMock(
            '\PreCommit\Processor\ErrorCollector',
            array('addError')
        );
        $errorCollector->expects($this->never())->method('addError');
        $str = <<<CONTENT
<?php
\$space = 1;
\$tab = 2;
\$noTail = 33;

CONTENT;

        $validator = new PreCommit\Validator\TrailingSpace(array('errorCollector' => $errorCollector));
        $validator->validate($str, '');
    }
}
