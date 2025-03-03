<?php declare(strict_types=1);

namespace Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType;

use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateTypeTranslation\MailTemplateTypeTranslationCollection;
use Shopware\Core\Content\MailTemplate\MailTemplateCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;

#[Package('after-sales')]
class MailTemplateTypeEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected string $name;

    protected string $technicalName;

    protected ?array $availableEntities = null;

    protected ?MailTemplateTypeTranslationCollection $translations = null;

    protected ?MailTemplateCollection $mailTemplates = null;

    protected ?array $templateData = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getTranslations(): ?MailTemplateTypeTranslationCollection
    {
        return $this->translations;
    }

    public function getAvailableEntities(): ?array
    {
        return $this->availableEntities;
    }

    public function setAvailableEntities(?array $availableEntities): void
    {
        $this->availableEntities = $availableEntities;
    }

    public function setTranslations(MailTemplateTypeTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getMailTemplates(): ?MailTemplateCollection
    {
        return $this->mailTemplates;
    }

    public function setMailTemplates(MailTemplateCollection $mailTemplates): void
    {
        $this->mailTemplates = $mailTemplates;
    }

    public function getTemplateData(): ?array
    {
        return $this->templateData;
    }

    public function setTemplateData(?array $templateData): void
    {
        $this->templateData = $templateData;
    }
}
