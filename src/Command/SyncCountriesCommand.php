<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\CountryDataProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:countries:sync', description: 'Sync country data from Symfony Intl and libphonenumber.')]
final class SyncCountriesCommand extends Command
{
    public function __construct(
        private readonly CountryDataProvider $countryDataProvider,
        private readonly CountryRepository $countryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Locale for country names.', 'en');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = (string) $input->getOption('locale');

        $countryData = $this->countryDataProvider->getAllCountryData($locale);
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($countryData as $row) {
            $country = $this->countryRepository->findOneByIso2Code($row['iso2Code']);
            if (null === $country) {
                $country = new Country();
                $this->applyData($country, $row);
                $this->entityManager->persist($country);
                ++$inserted;

                continue;
            }

            if ($this->applyData($country, $row)) {
                ++$updated;

                continue;
            }

            ++$skipped;
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Country sync complete. Inserted: %d, Updated: %d, Skipped: %d',
            $inserted,
            $updated,
            $skipped
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array{name: string, iso2Code: string, iso3Code: ?string, dialCode: string, flagEmoji: ?string, currencyCode: ?string, currencyIcon: ?string} $row
     */
    private function applyData(Country $country, array $row): bool
    {
        $changed = false;

        if ($country->getName() !== $row['name']) {
            $country->setName($row['name']);
            $changed = true;
        }

        if ($country->getIso2Code() !== $row['iso2Code']) {
            $country->setIso2Code($row['iso2Code']);
            $changed = true;
        }

        if ($country->getIso3Code() !== $row['iso3Code']) {
            $country->setIso3Code($row['iso3Code']);
            $changed = true;
        }

        if ($country->getDialCode() !== $row['dialCode']) {
            $country->setDialCode($row['dialCode']);
            $changed = true;
        }

        if ($country->getFlagEmoji() !== $row['flagEmoji']) {
            $country->setFlagEmoji($row['flagEmoji']);
            $changed = true;
        }

        if ($country->getCurrencyCode() !== $row['currencyCode']) {
            $country->setCurrencyCode($row['currencyCode']);
            $changed = true;
        }

        if ($country->getCurrencyIcon() !== $row['currencyIcon']) {
            $country->setCurrencyIcon($row['currencyIcon']);
            $changed = true;
        }

        if (!$country->isActive()) {
            $country->setIsActive(true);
            $changed = true;
        }

        return $changed;
    }
}
