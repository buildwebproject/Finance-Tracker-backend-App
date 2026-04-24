<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\FinanceCategory;
use App\Repository\FinanceCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:finance-categories:seed', description: 'Seed default finance categories if missing.')]
final class SeedFinanceCategoriesCommand extends Command
{
    private const DEFAULT_CATEGORIES = [
        'Food',
        'Transport',
        'Shopping',
        'Health',
        'Salary',
        'Freelance',
        'Bills',
        'Entertainment',
        'Education',
        'Gift',
    ];

    public function __construct(
        private readonly FinanceCategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $inserted = 0;

        foreach (self::DEFAULT_CATEGORIES as $name) {
            $existing = $this->categoryRepository->findOneBy(['name' => $name]);
            if ($existing instanceof FinanceCategory) {
                continue;
            }

            $category = new FinanceCategory();
            $category->setName($name);
            $category->setIsActive(true);
            $this->entityManager->persist($category);
            ++$inserted;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Finance categories seed complete. Inserted: %d', $inserted));

        return Command::SUCCESS;
    }
}
