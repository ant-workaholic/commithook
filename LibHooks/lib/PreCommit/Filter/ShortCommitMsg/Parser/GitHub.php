<?php
namespace PreCommit\Filter\ShortCommitMsg\Parser;

use PreCommit\Interpreter\InterpreterInterface;
use PreCommit\Filter\ShortCommitMsg;

/**
 * Class filter to parse short message
 *
 * @package PreCommit\Filter\ShortCommitMsg\GitHub
 */
class GitHub extends ShortCommitMsg\Parser\Jira implements InterpreterInterface
{
    /**
     * Convert issue number to issue key
     *
     * Add project key to issue number when it did not set.
     *
     * @param string $issueNo
     * @return string
     * @throws \PreCommit\Exception
     */
    protected function normalizeIssueKey($issueNo)
    {
        return "#" . ltrim($issueNo, '#');
    }
}