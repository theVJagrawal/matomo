<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\TestRunner\Commands;

use Piwik\AssetManager;
use Piwik\Config;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Tests\Framework\Fixture;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestsRunUI extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('tests:run-ui');
        $this->setDescription('Run screenshot tests');
        $this->setHelp("Example Commands
        \nRun one spec:
        \n./console tests:run-ui UIIntegrationTest
        ");
        $this->addArgument('specs', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Run only a specific test spec. Separate multiple specs by a space, for instance UIIntegrationTest ', array());
        $this->addOption("persist-fixture-data", null, InputOption::VALUE_NONE, "Persist test data in a database and do not execute tear down.");
        $this->addOption('keep-symlinks', null, InputOption::VALUE_NONE, "Keep recursive directory symlinks so test pages can be viewed in a browser.");
        $this->addOption('print-logs', null, InputOption::VALUE_NONE, "Print webpage logs even if tests succeed.");
        $this->addOption('drop', null, InputOption::VALUE_NONE, "Drop the existing database and re-setup a persisted fixture.");
        $this->addOption('assume-artifacts', null, InputOption::VALUE_NONE, "Assume the diffviewer and processed screenshots will be stored on the builds artifacts server. For use with CI build.");
        $this->addOption('plugin', null, InputOption::VALUE_REQUIRED, "Execute all tests for a plugin.");
        $this->addOption('core', null, InputOption::VALUE_NONE, "Execute only tests for Piwik core & core plugins.");
        $this->addOption('skip-delete-assets', null, InputOption::VALUE_NONE, "Skip deleting of merged assets (will speed up a test run, but not by a lot).");
        $this->addOption('screenshot-repo', null, InputOption::VALUE_NONE, "For tests");
        $this->addOption('store-in-ui-tests-repo', null, InputOption::VALUE_NONE, "For tests");
        $this->addOption('debug', null, InputOption::VALUE_NONE, "Enable phantomjs debugging");
        $this->addOption('extra-options', null, InputOption::VALUE_REQUIRED, "Extra options to pass to phantomjs.");
        $this->addOption('enable-logging', null, InputOption::VALUE_NONE, 'Enable logging to the configured log file during tests.');
        $this->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Custom test timeout value.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $specs = $input->getArgument('specs');
        $persistFixtureData = $input->getOption('persist-fixture-data');
        $keepSymlinks = $input->getOption('keep-symlinks');
        $printLogs = $input->getOption('print-logs');
        $drop = $input->getOption('drop');
        $assumeArtifacts = $input->getOption('assume-artifacts');
        $plugin = $input->getOption('plugin');
        $skipDeleteAssets = $input->getOption('skip-delete-assets');
        $core = $input->getOption('core');
        $extraOptions = $input->getOption('extra-options');
        $storeInUiTestsRepo = $input->getOption('store-in-ui-tests-repo');
        $screenshotRepo = $input->getOption('screenshot-repo');
        $debug = $input->getOption('debug');
        $matomoDomain = $input->getOption('matomo-domain');
        $enableLogging = $input->getOption('enable-logging');
        $timeout = $input->getOption('timeout');

        if (!$skipDeleteAssets) {
            AssetManager::getInstance()->removeMergedAssets();
        }

        $this->writeJsConfig();

        $options = array();
        $phantomJsOptions = array();

        if ($matomoDomain) {
            $options[] = "--matomo-domain=$matomoDomain";
        }

        if ($persistFixtureData) {
            $options[] = "--persist-fixture-data";
        }

        if ($keepSymlinks) {
            $options[] = "--keep-symlinks";
        }

        if ($printLogs) {
            $options[] = "--print-logs";
        }

        if ($drop) {
            $options[] = "--drop";
        }

        if ($assumeArtifacts) {
            $options[] = "--assume-artifacts";
        }

        if ($plugin) {
            $options[] = "--plugin=" . $plugin;
        }

        if ($core) {
            $options[] = "--core";
        }

        if ($storeInUiTestsRepo) {
            $options[] = "--store-in-ui-tests-repo";
        }

        if ($screenshotRepo) {
            $options[] = "--screenshot-repo";
        }

        if ($debug) {
            $phantomJsOptions[] = "--debug=true";
        }

        if ($enableLogging) {
            $options[] = '--enable-logging';
        }

        if ($extraOptions) {
            $options[] = $extraOptions;
        }

        if ($timeout !== false && $timeout > 0) {
            $options[] = "--timeout=" . (int) $timeout;
        }

        $options = implode(" ", $options);
        $phantomJsOptions = implode(" ", $phantomJsOptions);

        $specs = implode(" ", $specs);

        $screenshotTestingDir = PIWIK_INCLUDE_PATH . "/tests/lib/screenshot-testing/";
        $cmd = "cd '$screenshotTestingDir' && NODE_PATH='$screenshotTestingDir/node_modules' node " . $phantomJsOptions . " run-tests.js $options $specs";

        $output->writeln('Executing command: <info>' . $cmd . '</info>');
        $output->writeln('');

        passthru($cmd, $returnCode);

        return $returnCode;
    }

    /**
     * We override the default values of tests/UI/config.dist.js with config
     * values from the local INI config.
     */
    private function writeJsConfig()
    {
        $localConfigFile = PIWIK_INCLUDE_PATH . '/tests/UI/config.js';
        $tag = 'File generated by the tests:run-ui command';

        // If the file wasn't generated by this command, we don't ovewrite it
        if (file_exists($localConfigFile)) {
            $fileContent = file_get_contents($localConfigFile);
            if (strpos($fileContent, $tag) === false) {
                return;
            }
        }

        $url = Fixture::getRootUrl();
        $host = Config::getInstance()->tests['http_host'];
        $uri = Config::getInstance()->tests['request_uri'];

        $js = <<<JS
/**
 * $tag
 */
exports.piwikUrl = "$url";
exports.phpServer = {
    HTTP_HOST: '$host',
    REQUEST_URI: '$uri',
    REMOTE_ADDR: '127.0.0.1'
};
JS;

        file_put_contents(PIWIK_INCLUDE_PATH . '/tests/UI/config.js', $js);
    }
}
