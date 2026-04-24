<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Country;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:countries:cleanup-invalid-currency',
    description: 'Remove countries with missing currency code/icon or word-like currency icons.'
)]
final class CleanupCountriesInvalidCurrencyCommand extends Command
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Actually delete rows. Without this option, command runs in dry-run mode.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');

        /** @var list<Country> $countries */
        $countries = $this->countryRepository->findBy([], ['name' => 'ASC']);

        $toDelete = [];
        $previewRows = [];

        foreach ($countries as $country) {
            $reasons = $this->buildRemovalReasons($country);
            if ([] === $reasons) {
                continue;
            }

            $toDelete[] = $country;
            $previewRows[] = [
                $country->getId() ?? 0,
                $country->getName(),
                $country->getIso2Code(),
                $country->getCurrencyCode() ?? '-',
                $country->getCurrencyIcon() ?? '-',
                implode('; ', $reasons),
            ];
        }

        if ([] === $toDelete) {
            $io->success('No countries matched cleanup rules.');

            return Command::SUCCESS;
        }

        $io->section(sprintf('Matched countries: %d', count($toDelete)));
        $io->table(['ID', 'Name', 'ISO2', 'Currency Code', 'Currency Icon', 'Reason'], $previewRows);

        if (!$apply) {
            $io->warning('Dry-run mode: no rows were deleted. Re-run with --apply to delete matched countries.');

            return Command::SUCCESS;
        }

        foreach ($toDelete as $country) {
            $this->entityManager->remove($country);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Deleted %d countries.', count($toDelete)));

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function buildRemovalReasons(Country $country): array
    {
        $reasons = [];

        $currencyCode = $country->getCurrencyCode();
        $currencyIcon = $country->getCurrencyIcon();

        if (null === $currencyCode || '' === trim($currencyCode)) {
            $reasons[] = 'missing currency_code';
        }

        if (null === $currencyIcon || '' === trim($currencyIcon)) {
            $reasons[] = 'missing currency_icon';
        }

        if (null !== $currencyIcon && '' !== trim($currencyIcon) && 1 === preg_match('/\p{L}/u', $currencyIcon)) {
            $reasons[] = 'currency_icon looks like text';
        }

        return $reasons;
    }
}
