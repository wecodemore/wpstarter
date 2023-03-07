<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

final class ScriptHaltSignal
{
    /**
     * @var string
     */
    private $reason;
    /**
     * @var bool
     */
    private $propagationStopped;
    /**
     * @var bool
     */
    private $stepHalted;

    /**
     * @param string $reason
     * @return ScriptHaltSignal
     */
    public static function stopPropagation(string $reason): ScriptHaltSignal
    {
        return new self($reason, true, false);
    }

    /**
     * @param string $reason
     * @return ScriptHaltSignal
     */
    public static function haltStep(string $reason): ScriptHaltSignal
    {
        return new self($reason, true, true);
    }

    /**
     * @param string $reason
     * @return ScriptHaltSignal
     */
    public static function haltStepContinuePropagation(string $reason): ScriptHaltSignal
    {
        return new self($reason, false, true);
    }

    /**
     * @param string $reason
     * @param bool $propagationStopped
     * @param bool $stepHalted
     */
    private function __construct(string $reason, bool $propagationStopped, bool $stepHalted)
    {
        $this->reason = $reason;
        $this->propagationStopped = $propagationStopped;
        $this->stepHalted = $stepHalted;
    }

    /**
     * @return string
     */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * @return bool
     */
    public function isStepHalted(): bool
    {
        return $this->stepHalted;
    }
}
