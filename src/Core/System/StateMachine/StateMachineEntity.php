<?php declare(strict_types=1);

namespace Shopware\Core\System\StateMachine;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionCollection;

#[Package('checkout')]
class StateMachineEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected string $technicalName;

    protected ?string $name = null;

    protected ?StateMachineTransitionCollection $transitions = null;

    protected ?StateMachineStateCollection $states = null;

    protected ?string $initialStateId = null;

    protected ?StateMachineTranslationCollection $translations = null;

    protected ?StateMachineHistoryCollection $historyEntries = null;

    public function getHistoryEntries(): ?StateMachineHistoryCollection
    {
        return $this->historyEntries;
    }

    public function setHistoryEntries(StateMachineHistoryCollection $historyEntries): void
    {
        $this->historyEntries = $historyEntries;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getTransitions(): ?StateMachineTransitionCollection
    {
        return $this->transitions;
    }

    public function setTransitions(StateMachineTransitionCollection $transitions): void
    {
        $this->transitions = $transitions;
    }

    public function getStates(): ?StateMachineStateCollection
    {
        return $this->states;
    }

    public function setStates(StateMachineStateCollection $states): void
    {
        $this->states = $states;
    }

    public function getInitialState(): ?StateMachineStateEntity
    {
        foreach ($this->states as $state) {
            if ($state->getId() === $this->initialStateId) {
                return $state;
            }
        }

        return null;
    }

    public function getInitialStateId(): ?string
    {
        return $this->initialStateId;
    }

    public function setInitialStateId(string $initialStateId): void
    {
        $this->initialStateId = $initialStateId;
    }

    public function getTranslations(): ?StateMachineTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(StateMachineTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }
}
