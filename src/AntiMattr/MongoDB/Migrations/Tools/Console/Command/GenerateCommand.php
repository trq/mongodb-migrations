<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class GenerateCommand extends AbstractCommand
{

    private static $_template =
            '<?php

namespace <namespace>;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use Doctrine\MongoDB\Database;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version<version> extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return "";
    }

    public function up(Database $db)
    {
        // this up() migration is auto-generated, please modify it to your needs
<up>
    }

    public function down(Database $db)
    {
        // this down() migration is auto-generated, please modify it to your needs
<down>
    }
}
';

    protected function configure()
    {
        $this
                ->setName('mongodb:migrations:generate')
                ->setDescription('Generate a blank migration class.')
                ->addOption('editor-cmd', null, InputOption::VALUE_OPTIONAL, 'Open file with this command upon creation.')
                ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
        );

        parent::configure();
    }

    /**
     * @param Symfony\Component\Console\Input\InputInterface
     * @param Symfony\Component\Console\Output\OutputInterface
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $version = date('YmdHis');
        $path = $this->generateMigration($configuration, $input, $version);

        $output->writeln(sprintf('Generated new migration class to "<info>%s</info>"', $path));
    }

    /**
     * @param AntiMattr\MongoDB\Migrations\Configuration\Configuration
     * @param Symfony\Component\Console\Input\InputInterface
     * @param string $version
     * @param string $up
     * @param string $down
     *
     * @return string $path
     *
     * @throws InvalidArgumentException
     */
    protected function generateMigration(Configuration $configuration, InputInterface $input, $version, $up = null, $down = null)
    {
        $placeHolders = array(
            '<namespace>',
            '<version>',
            '<up>',
            '<down>'
        );
        $replacements = array(
            $configuration->getMigrationsNamespace(),
            $version,
            $up ? "        ".implode("\n        ", explode("\n", $up)) : null,
            $down ? "        ".implode("\n        ", explode("\n", $down)) : null
        );
        $code = str_replace($placeHolders, $replacements, self::$_template);
        $dir = $configuration->getMigrationsDirectory();
        $dir = $dir ? $dir : getcwd();
        $dir = rtrim($dir, '/');
        $path = $dir.'/Version'.$version.'.php';

        if (!file_exists($dir)) {
            throw new \InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $dir));
        }

        file_put_contents($path, $code);

        if ($editorCmd = $input->getOption('editor-cmd')) {
            shell_exec($editorCmd.' '.escapeshellarg($path));
        }

        return $path;
    }
}