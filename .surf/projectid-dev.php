<?php
/** @var \TYPO3\Surf\Domain\Model\Deployment $deployment */

use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;

$projectName = 'PROJECTNAME - Dev';
$deploymentPath = '/path/to/deploy';
$deploymentHost = 'PROJECTID-dev';

// Set this if you do not have a remote repository
// $repositoryUrl = 'PROJECTREPOURL';

// Set this if you want to deploy a different branch than master
//$repositoryBranch = 'master';

// Set this if your composer command is not available in PATH
//$composerCommandPath = 'composer';

// Set this, if on remote host the correct PHP binary is not available in PATH
//$deployment->setOption('phpBinaryPathAndFilename', '/usr/local/bin/php5-56LATEST-CLI');



// No changes are required in the default case below this point.
$application = new \TYPO3\Surf\Application\TYPO3\CMS();
$deployment->addApplication($application);


// Set default config values
if (!isset($repositoryUrl)) {
    $repositoryUrl = 'file://' . dirname(__DIR__);
}
if (!isset($repositoryBranch)) {
    $repositoryBranch = getenv('DEPLOY_BRANCH') ?: 'master';
}
if (!isset($composerCommandPath)) {
    $composerCommandPath = 'composer';
}


$node = new Node($deploymentHost);
$node->setHostname($deploymentHost);

$application->addNode($node);

$application->setOption('projectName', $projectName);
$application->setOption('repositoryUrl', $repositoryUrl);
$application->setOption('branch', $repositoryBranch);

$application->setDeploymentPath($deploymentPath);
$application->setOption('keepReleases', 1);
$application->setOption('composerCommandPath', $composerCommandPath);

// Make sure we build from a clean state
$application->setOption('TYPO3\\Surf\\Task\\Package\\GitTask[hardClean]', true);
$application->setContext('Production');

$deployment->onInitialize(function() use ($deployment) {
    /** @var SimpleWorkflow $workflow */
    $workflow = $deployment->getWorkflow();
    $workflow->setEnableRollback(FALSE);

    $workflow->defineTask('Helhum\\TYPO3\\Distribution\\DefinedTask\\EnvAwareTask', 'TYPO3\\Surf\\Task\\ShellTask', array(
        'command' => array(
            "cp {sharedPath}/.env {releasePath}/.env",
            "cd {releasePath}",
        )
    ));
    $workflow->defineTask('Helhum\\TYPO3\\Distribution\\DefinedTask\\CopyIndexPhp', 'TYPO3\\Surf\\Task\\ShellTask', array(
        'command' => array(
            "rm {releasePath}/web/index.php",
            "cp {releasePath}/vendor/typo3/cms/index.php {releasePath}/web/index.php",
        )
    ));

    $workflow->afterStage('transfer', 'Helhum\\TYPO3\\Distribution\\DefinedTask\\CopyIndexPhp');
    $workflow->afterTask('TYPO3\\Surf\\Task\\Generic\\CreateSymlinksTask', 'Helhum\\TYPO3\\Distribution\\DefinedTask\\EnvAwareTask');
    $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CreatePackageStatesTask');
    $workflow->forStage('finalize', 'TYPO3\\Surf\\Task\\TYPO3\\CMS\\FlushCachesTask');
});
