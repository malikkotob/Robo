<?php
namespace Robo\Task\Development;

use Robo\Task\BaseTask;
use Robo\Task\File\Replace;
use Robo\Task\Filesystem;
use Robo\Result;
use Robo\Task\Development;
use Robo\Contract\BuilderAwareInterface;
use Robo\Common\BuilderAwareTrait;

/**
 * Helps to manage changelog file.
 * Creates or updates `changelog.md` file with recent changes in current version.
 *
 * ``` php
 * <?php
 * $version = "0.1.0";
 * $this->taskChangelog()
 *  ->version($version)
 *  ->change("released to github")
 *  ->run();
 * ?>
 * ```
 *
 * Changes can be asked from Console
 *
 * ``` php
 * <?php
 * $this->taskChangelog()
 *  ->version($version)
 *  ->askForChanges()
 *  ->run();
 * ?>
 * ```
 */
class Changelog extends BaseTask implements BuilderAwareInterface
{
    use BuilderAwareTrait;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var array
     */
    protected $log = [];

    /**
     * @var string
     */
    protected $anchor = "# Changelog";

    /**
     * @var string
     */
    protected $version = "";

    /**
     * @param string $filename
     *
     * @return $this
     */
    public function filename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @param string $item
     *
     * @return $this
     */
    public function log($item)
    {
        $this->log[] = $item;
        return $this;
    }

    /**
     * @param string $anchor
     *
     * @return $this
     */
    public function anchor($anchor)
    {
        $this->anchor = $anchor;
        return $this;
    }

    /**
     * @param string $version
     *
     * @return $this
     */
    public function version($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function changes(array $data)
    {
        $this->log = array_merge($this->log, $data);
        return $this;
    }

    /**
     * @param string $change
     *
     * @return $this
     */
    public function change($change)
    {
        $this->log[] = $change;
        return $this;
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->log;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (empty($this->log)) {
            return Result::error($this, "Changelog is empty");
        }
        $text = implode(
            "\n",
            array_map(
                function ($i) {
                        return "* $i *" . date('Y-m-d') . "*";
                },
                $this->log
            )
        ) . "\n";
        $ver = "#### {$this->version}\n\n";
        $text = $ver . $text;

        if (!file_exists($this->filename)) {
            $this->printTaskInfo('Creating {filename}', ['filename' => $this->filename]);
            $res = file_put_contents($this->filename, $this->anchor);
            if ($res === false) {
                return Result::error($this, "File {filename} cant be created", ['filename' => $this->filename]);
            }
        }

        /** @var \Robo\Result $result */
        // trying to append to changelog for today
        $result = $this->collectionBuilder()->taskReplace($this->filename)
            ->from($ver)
            ->to($text)
            ->run();

        if (!isset($result['replaced']) || !$result['replaced']) {
            $result = $this->collectionBuilder()->taskReplace($this->filename)
                ->from($this->anchor)
                ->to($this->anchor . "\n\n" . $text)
                ->run();
        }

        return new Result($this, $result->getExitCode(), $result->getMessage(), $this->log);
    }
}
