<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Dumper;

use Symfony\Component\VarDumper\Cloner\Data;

/**
 * Abstract mechanism for dumping a Data object.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
abstract class AbstractDumper implements DataDumperInterface, DumperInternalsInterface
{
    public static $defaultOutputStream = 'php://output';

    protected $line = '';
    protected $lineDumper;
    protected $outputStream;

    /**
     * @param callable|resource|string|null $outputStream A line dumper callable, an opened stream or an output path, defaults to static::$defaultOutputStream.
     */
    public function __construct($outputStream = null)
    {
        if (is_callable($outputStream)) {
            $this->setLineDumper($outputStream);
        } else {
            if (!isset($outputStream)) {
                $outputStream =& static::$defaultOutputStream;
            }
            if (is_string($outputStream)) {
                $outputStream = fopen($outputStream, 'wb');
            }
            $this->outputStream = $outputStream;
            $this->setLineDumper(array($this, 'echoLine'));
        }
    }

    /**
     * Sets a line dumper callback.
     *
     * @param callable $callback A callback responsible for writing the dump, one line at a time.
     *
     * @return callable|null The previous line dumper.
     */
    public function setLineDumper($callback)
    {
        $prev = $this->lineDumper;
        $this->lineDumper = $callback;

        return $prev;
    }

    /**
     * Dumps a Data object.
     *
     * @param Data          $data       A Data object.
     * @param callable|null $lineDumper A callback for writing dump's lines.
     */
    public function dump(Data $data, $lineDumper = null)
    {
        $dumper = clone $this;
        if ($lineDumper) {
            $dumper->setLineDumper($lineDumper);
        }
        $data->dump($dumper);
        $dumper->dumpLine(false);
    }

    /**
     * Dumps the current line.
     *
     * @param int $depth The recursive depth in the dumped structure for the line being dumped.
     */
    protected function dumpLine($depth)
    {
        call_user_func($this->lineDumper, $this->line, $depth);
        $this->line = '';
    }

    /**
     * Generic line dumper callback.
     *
     * @param string $line  The line to write.
     * @param int    $depth The recursive depth in the dumped structure.
     */
    protected function echoLine($line, $depth)
    {
        if (false !== $depth) {
            fwrite($this->outputStream, "\n".str_repeat('  ', $depth).$line);
        } else {
            fwrite($this->outputStream, "\n");
        }
    }
}
