<?php declare(strict_types=1);

namespace Shopware\Core\Maintenance\System\Service;

use Doctrine\DBAL\Connection;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableTransaction;
use Shopware\Core\Framework\DataAbstractionLayer\Util\StatementHelper;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Maintenance\MaintenanceException;
use Symfony\Component\Intl\Currencies;

/**
 * @internal
 */
#[Package('framework')]
class ShopConfigurator
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function updateBasicInformation(?string $shopName, ?string $email): void
    {
        if ($shopName) {
            $this->setSystemConfig('core.basicInformation.shopName', $shopName);
        }

        if ($email) {
            $this->setSystemConfig('core.basicInformation.email', $email);
        }
    }

    public function setDefaultLanguage(string $locale): void
    {
        $locale = str_replace('_', '-', $locale);

        $currentLocale = $this->getCurrentSystemLocale();
        if (!$currentLocale) {
            throw MaintenanceException::shopConfigurationNotValid('Default language locale not found');
        }

        $currentLocaleId = $currentLocale['id'];
        $newDefaultLocaleId = $this->getLocaleId($locale);

        // locales match -> do nothing.
        if ($currentLocaleId === $newDefaultLocaleId) {
            return;
        }

        $newDefaultLanguageId = $this->getLanguageId($locale);

        if (!$newDefaultLanguageId) {
            $newDefaultLanguageId = $this->createNewLanguageEntry($locale);
        }

        if ($locale === 'de-DE' && $currentLocale['code'] === 'en-GB') {
            $defaultCountryStateTranslations = $this->connection->fetchAllKeyValue('
            SELECT short_code, name FROM country_state_translation
            INNER JOIN country_state ON country_state.id = country_state_translation.country_state_id
            WHERE country_state_translation.language_id = :languageId', [
                'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ]);

            if (!empty($defaultCountryStateTranslations)) {
                $correctDeTranslations = [
                    'DE-BW' => 'Baden-Württemberg',
                    'DE-BY' => 'Bayern',
                    'DE-BE' => 'Berlin',
                    'DE-BB' => 'Brandenburg',
                    'DE-HB' => 'Bremen',
                    'DE-HH' => 'Hamburg',
                    'DE-HE' => 'Hessen',
                    'DE-NI' => 'Niedersachsen',
                    'DE-MV' => 'Mecklenburg-Vorpommern',
                    'DE-NW' => 'Nordrhein-Westfalen',
                    'DE-RP' => 'Rheinland-Pfalz',
                    'DE-SL' => 'Saarland',
                    'DE-SN' => 'Sachsen',
                    'DE-ST' => 'Sachsen-Anhalt',
                    'DE-SH' => 'Schleswig-Holstein',
                    'DE-TH' => 'Thüringen',
                ];

                foreach ($defaultCountryStateTranslations as $shortCode => $deTranslation) {
                    if (!\array_key_exists($shortCode, $correctDeTranslations)) {
                        continue;
                    }

                    $defaultCountryStateTranslations[$shortCode] = $correctDeTranslations[$shortCode];
                }
            }

            $this->swapDefaultLanguageId($newDefaultLanguageId);
            $this->addMissingCountryStates($defaultCountryStateTranslations);
        } else {
            $this->changeDefaultLanguageData($newDefaultLanguageId, $currentLocale, $locale);
        }

        $this->eventDispatcher->dispatch(new SystemLanguageChangeEvent(Uuid::fromBytesToHex($newDefaultLanguageId)));
    }

    public function setDefaultCurrency(string $currencyCode): void
    {
        $currentCurrencyIso = $this->connection->fetchOne(
            'SELECT iso_code FROM currency WHERE id = :currencyId',
            ['currencyId' => Uuid::fromHexToBytes(Defaults::CURRENCY)]
        );

        if (!$currentCurrencyIso) {
            throw MaintenanceException::shopConfigurationNotValid('Default currency not found');
        }

        if (\mb_strtoupper($currentCurrencyIso) === \mb_strtoupper($currencyCode)) {
            return;
        }

        $newDefaultCurrencyId = $this->getCurrencyId($currencyCode);
        if (!$newDefaultCurrencyId) {
            $newDefaultCurrencyId = $this->createNewCurrency($currencyCode);
        }

        RetryableTransaction::retryable($this->connection, static function (Connection $conn) use ($newDefaultCurrencyId, $currencyCode): void {
            $stmt = $conn->prepare('UPDATE currency SET id = :newId WHERE id = :oldId');

            // assign new uuid to old DEFAULT
            StatementHelper::executeStatement($stmt, [
                'newId' => Uuid::randomBytes(),
                'oldId' => Uuid::fromHexToBytes(Defaults::CURRENCY),
            ]);

            // change id to DEFAULT
            StatementHelper::executeStatement($stmt, [
                'newId' => Uuid::fromHexToBytes(Defaults::CURRENCY),
                'oldId' => $newDefaultCurrencyId,
            ]);

            $conn->executeStatement(
                'SET @fixFactor = (SELECT 1/factor FROM currency WHERE iso_code = :newDefault);
                 UPDATE currency
                 SET factor = IF(iso_code = :newDefault, 1, factor * @fixFactor);',
                ['newDefault' => $currencyCode]
            );
        });
    }

    /**
     * @param array<int|string, mixed> $defaultTranslations
     */
    private function addMissingCountryStates(array $defaultTranslations): void
    {
        $missingTranslations = $this->connection->fetchAllKeyValue('
            SELECT id, short_code FROM `country_state`
            WHERE id NOT IN (
                SELECT country_state_id FROM country_state_translation WHERE language_id = :languageId GROUP BY country_state_id
            )', [
            'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
        ]);

        if (empty($missingTranslations)) {
            return;
        }

        $storageDate = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($missingTranslations as $stateId => $shortCode) {
            if (!\array_key_exists($shortCode, $defaultTranslations)) {
                continue;
            }

            $this->connection->insert('country_state_translation', [
                'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                'country_state_id' => $stateId,
                'name' => $defaultTranslations[$shortCode],
                'created_at' => $storageDate,
            ]);
        }
    }

    private function setSystemConfig(string $key, string $value): void
    {
        $value = json_encode(['_value' => $value], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_PRESERVE_ZERO_FRACTION);

        // Fetch id for config key, as the unique key on config_key and salesChannelId will not work when salesChannelId is null
        $id = $this->connection->fetchOne('
            SELECT `id`
            FROM `system_config`
            WHERE `configuration_key` = :key AND `sales_channel_id` IS NULL
        ', ['key' => $key]);

        if (!$id) {
            $id = Uuid::randomBytes();
        }

        $this->connection->executeStatement('
            INSERT INTO `system_config` (`id`, `configuration_key`, `configuration_value`, `sales_channel_id`, `created_at`)
            VALUES (:id, :key, :value, NULL, NOW())
            ON DUPLICATE KEY UPDATE
                `configuration_value` = :value,
                `updated_at` = NOW()
        ', [
            'id' => $id,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * @param array<string, string> $currentLocaleData
     */
    private function changeDefaultLanguageData(string $newDefaultLanguageId, array $currentLocaleData, string $locale): void
    {
        $enGbLanguageId = $this->getLanguageId('en-GB');
        $currentLocaleId = $currentLocaleData['id'];
        $name = $locale;

        $newDefaultLocaleId = $this->getLocaleId($locale);

        if (!$newDefaultLanguageId && $enGbLanguageId) {
            $name = $this->connection->fetchFirstColumn(
                'SELECT name FROM locale_translation
                 WHERE language_id = :languageId
                 AND locale_id = :localeId',
                ['languageId' => $enGbLanguageId, 'localeId' => $newDefaultLocaleId]
            );
        }

        RetryableTransaction::retryable($this->connection, function (Connection $connection) use ($locale, $currentLocaleId, $newDefaultLocaleId, $currentLocaleData, $newDefaultLanguageId, $name): void {
            // swap locale.code
            $stmt = $connection->prepare(
                'UPDATE locale SET code = :code WHERE id = :locale_id'
            );
            StatementHelper::executeStatement($stmt, ['code' => 'x-' . $locale . '_tmp', 'locale_id' => $currentLocaleId]);
            StatementHelper::executeStatement($stmt, ['code' => $currentLocaleData['code'], 'locale_id' => $newDefaultLocaleId]);
            StatementHelper::executeStatement($stmt, ['code' => $locale, 'locale_id' => $currentLocaleId]);

            // swap locale_translation.{name,territory}
            $setTrans = $connection->prepare(
                'UPDATE locale_translation
                 SET name = :name, territory = :territory
                 WHERE locale_id = :locale_id AND language_id = :language_id'
            );

            $currentTrans = $this->getLocaleTranslations($currentLocaleId);
            $newDefTrans = $this->getLocaleTranslations($newDefaultLocaleId);

            foreach ($currentTrans as $trans) {
                $trans['locale_id'] = $newDefaultLocaleId;
                StatementHelper::executeStatement($setTrans, $trans);
            }
            foreach ($newDefTrans as $trans) {
                $trans['locale_id'] = $currentLocaleId;
                StatementHelper::executeStatement($setTrans, $trans);
            }

            $updLang = $connection->prepare('UPDATE language SET name = :name WHERE id = :languageId');

            // new default language does not exist -> just set to name
            if (!$newDefaultLanguageId) {
                StatementHelper::executeStatement($updLang, ['name' => $name, 'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]);

                return;
            }

            $langName = $connection->prepare('SELECT name FROM language WHERE id = :languageId');

            $current = StatementHelper::executeQuery($langName, ['languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)])->fetchOne();

            $new = StatementHelper::executeQuery($langName, ['languageId' => $newDefaultLanguageId])->fetchOne();

            // swap name
            StatementHelper::executeStatement($updLang, ['name' => $new, 'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]);
            StatementHelper::executeStatement($updLang, ['name' => $current, 'languageId' => $newDefaultLanguageId]);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLocaleTranslations(string $localeId): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT locale_id, language_id, name, territory
             FROM locale_translation
             WHERE locale_id = :localeId',
            ['localeId' => $localeId]
        );
    }

    private function getLanguageId(string $iso): ?string
    {
        return $this->connection->fetchOne(
            'SELECT language.id
             FROM `language`
             INNER JOIN locale ON locale.id = language.translation_code_id
             WHERE LOWER(locale.code) = LOWER(:iso)',
            ['iso' => $iso]
        ) ?: null;
    }

    private function getLocaleId(string $iso): string
    {
        $id = $this->connection->fetchOne(
            'SELECT locale.id FROM  locale WHERE LOWER(locale.code) = LOWER(:iso)',
            ['iso' => $iso]
        );

        if (!$id) {
            throw MaintenanceException::shopConfigurationNotValid(\sprintf('Locale with iso-code "%s" not found', $iso));
        }

        return $id;
    }

    private function createNewLanguageEntry(string $iso): string
    {
        $id = Uuid::randomBytes();

        $localeId = $this->connection->fetchOne(
            '
            SELECT locale.id
            FROM `locale`
            WHERE LOWER(locale.code) = LOWER(:iso)',
            ['iso' => $iso]
        );

        $englishId = $this->connection->fetchOne(
            '
            SELECT language.id
            FROM `language`
            WHERE LOWER(language.name) = LOWER("english")'
        );

        // Always use the English name since we don't have the name in the language itself
        $name = $this->connection->fetchOne(
            '
            SELECT locale_translation.name
            FROM `locale_translation`
            WHERE locale_id = :localeId
            AND language_id = :languageId',
            ['localeId' => $localeId, 'languageId' => $englishId]
        );

        if (!$name) {
            throw MaintenanceException::shopConfigurationNotValid(\sprintf('locale_translation.name for iso "%s" and localeId "%s" not found', $iso, $localeId));
        }

        $this->connection->executeStatement(
            '
            INSERT INTO `language`
            (id, name, locale_id, translation_code_id, created_at)
            VALUES
            (:id, :name, :localeId, :localeId, NOW())',
            ['id' => $id, 'name' => $name, 'localeId' => $localeId]
        );

        return $id;
    }

    private function swapDefaultLanguageId(string $newLanguageId): void
    {
        RetryableTransaction::retryable($this->connection, static function (Connection $connection) use ($newLanguageId): void {
            $stmt = $connection->prepare(
                'UPDATE language
             SET id = :newId
             WHERE id = :oldId'
            );

            // assign new uuid to old DEFAULT
            StatementHelper::executeStatement($stmt, [
                'newId' => Uuid::randomBytes(),
                'oldId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ]);

            // change id to DEFAULT
            StatementHelper::executeStatement($stmt, [
                'newId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                'oldId' => $newLanguageId,
            ]);
        });
    }

    private function getCurrencyId(string $currencyName): ?string
    {
        return $this->connection->fetchOne(
            'SELECT id FROM currency WHERE LOWER(iso_code) = LOWER(:currency)',
            ['currency' => $currencyName]
        ) ?: null;
    }

    private function createNewCurrency(string $currencyCode): string
    {
        $id = Uuid::randomBytes();
        $currencyCode = \mb_strtoupper($currencyCode);

        if (!Currencies::exists($currencyCode)) {
            throw MaintenanceException::shopConfigurationNotValid(\sprintf('Currency with iso code "%s" not found', $currencyCode));
        }

        $fractionDigits = Currencies::getFractionDigits($currencyCode);
        $roundingIncrement = Currencies::getRoundingIncrement($currencyCode) === 0 ? 1 : Currencies::getRoundingIncrement($currencyCode);
        $rounding = [
            'decimals' => $fractionDigits,
            'interval' => $roundingIncrement * (10 ** ($fractionDigits * -1)),
            'roundForNet' => true,
        ];

        $this->connection->executeStatement('
            INSERT INTO `currency` (`id`, `iso_code`, `factor`, `symbol`, `position`, `item_rounding`, `total_rounding`, `created_at`)
            VALUES (:id, :currency, 1, :symbol, 1, :rounding, :rounding, NOW())
        ', ['id' => $id, 'currency' => $currencyCode, 'symbol' => Currencies::getSymbol($currencyCode), 'rounding' => json_encode($rounding, \JSON_THROW_ON_ERROR)]);

        $locale = $this->getCurrentSystemLocale();
        if ($locale) {
            $locale = str_replace('-', '_', $locale['code']);
        } else {
            $locale = 'en_GB';
        }
        $this->connection->executeStatement('
            INSERT INTO `currency_translation` (`currency_id`, `language_id`, `short_name`, `name`, `created_at`)
            VALUES (:currencyId, :languageId, :currency, :name, NOW())
        ', [
            'currencyId' => $id,
            'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            'currency' => $currencyCode,
            'name' => Currencies::getName($currencyCode, $locale),
        ]);

        return $id;
    }

    /**
     * @return array<string, string>|null
     */
    private function getCurrentSystemLocale(): ?array
    {
        return $this->connection->fetchAssociative(
            'SELECT locale.id, locale.code
             FROM language
             INNER JOIN locale ON translation_code_id = locale.id
             WHERE language.id = :languageId',
            ['languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
        ) ?: null;
    }
}
