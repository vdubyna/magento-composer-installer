<?php

/**
 * Composer Magento Installer
 *
 * BackwardDeploy
 *
 *
 */

namespace MagentoHackathon\Composer\Magento\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoHackathon\Composer\Magento\Command\Util\Filesystem;

/**
 * Class BackwardDeployCommand
 *
 * @package MagentoHackathon\Composer\Magento\Command
 */
class BackwardDeployCommand extends \Composer\Command\Command
{
    protected function configure()
    {
        $this
            ->setName('magento-module-backward-deploy')
            ->setDescription('Copy changed files based on files map in composer.json')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the module for backward copy')
            ->setHelp(<<<EOT
This command copy changed files back to repository in vendor directory to commit them.
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $composer = $this->getComposer();
        $installedRepo = $composer->getRepositoryManager()->getLocalRepository();

        $im = $composer->getInstallationManager();


        $vendorDir = rtrim($composer->getConfig()->get('vendor-dir'), '/');

        $extra = $composer->getPackage()->getExtra();
        if (isset($extra['magento-root-dir'])) {
            $dir = rtrim(trim($extra['magento-root-dir']), '/');
            $magentoRootDir = new \SplFileInfo($dir);
        } else {
            $output->writeln("Magento Root dir is not installed");
            return;
        }

        /** @var $moduleInstaller \MagentoHackathon\Composer\Magento\Installer */
        $moduleInstaller = $im->getInstaller("magento-module");

        $fs = new Filesystem();

        foreach ($installedRepo->getPackages() as $package) {
            /** @var $package \Composer\Package\CompletePackage */

            if($package->getType() != "magento-module" || $name != $package->getName()){
                continue;
            }

            $map = $moduleInstaller->getParser($package)->getMappings();

            foreach ($map as $item) {

                $sourcePath = $magentoRootDir . '/' . $item[1];
                $targetPath = $vendorDir . '/' . $package->getPrettyName() . '/' . $item[0];

                if (is_file($sourcePath)) {
                    $output->writeln("File {$sourcePath} copied");

                    $fs->copy($sourcePath, $targetPath);
                }

                if (is_dir($sourcePath)) {
                    $output->writeln("Directory {$sourcePath} copied");
                    $fs->mirror($sourcePath, $targetPath);
                }
            }
        }

        return;
    }
}