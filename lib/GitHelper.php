<?php


//load in git class through composer autoload
require_once realpath(dirname(__FILE__)) . '/../vendor/autoload.php';

/**
 * Class GitHelper
 *   Extends a little bit the git class , so it can be called without figuring out where the repo is and also to get the last commit id
 * @example
[2] boris> $a = new GitHelper();
$a = new GitHelper();
4] boris> $a->getCurrentBranchName();
// 'master'
[5] boris> $a->getCurrentCommit();
// '8b584b7'
[6] boris> $a->hasChanges();
// true

 */

class GitHelper extends \Cz\Git\GitRepository
{
    /**
     * GitHelper constructor.
     * @throws \Cz\Git\GitException if repo directory is missing
     */
    public function __construct() {
        $path_to_repo = realpath(dirname(__FILE__)) . '/../.git';
        parent::__construct($path_to_repo);
    }

    /** @noinspection SpellCheckingInspection */

    /**
     * gets the full hash of the last commit
     * @return string
     * @example  8b584b72a7238a9b6340653738caeb9f7bb409a7e3
     */
    public function getCurrentCommit()
    {

        $this->begin();
        $short_hash = exec('git rev-parse  HEAD');
        $this->end();
        return $short_hash;
    }


    /**
     * gets the short hash of the last commit
     * @return string
     * @example  31353e3
     */
    public function getCurrentShortCommit()
    {

        $this->begin();
        $short_hash = exec('git rev-parse --short HEAD');
        $this->end();
        return $short_hash;
    }
}

