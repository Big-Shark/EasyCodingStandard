<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Console\Output;

use Nette\Utils\Json;
use Symfony\Component\Console\Output\OutputInterface;
use Symplify\EasyCodingStandard\Configuration\Option;
use Symplify\EasyCodingStandard\Contract\Console\Output\OutputFormatterInterface;
use Symplify\EasyCodingStandard\Error\Error;
use Symplify\EasyCodingStandard\Error\ErrorAndDiffCollector;
use Symplify\EasyCodingStandard\Error\FileDiff;
use Symplify\PackageBuilder\Console\ShellCode;

final class JsonOutputFormatter implements OutputFormatterInterface
{
    /**
     * @var ErrorAndDiffCollector
     */
    private $errorAndDiffCollector;

    public function __construct(ErrorAndDiffCollector $errorAndDiffCollector)
    {
        $this->errorAndDiffCollector = $errorAndDiffCollector;
    }

    public function report(int $processedFilesCount, OutputInterface $output): int
    {
        $errorsArray = [
            'totals' => [
                'errors' => $this->errorAndDiffCollector->getErrorCount(),
                'diffs' => $this->errorAndDiffCollector->getFileDiffsCount(),
            ],
            'files' => [],
        ];

        /** @var Error[] $errors */
        foreach ($this->errorAndDiffCollector->getErrors() as $file => $errors) {
            foreach ($errors as $error) {
                $errorsArray['files'][$file]['errors'][] = [
                    'line' => $error->getLine(),
                    'message' => $error->getMessage(),
                    'sourceClass' => $error->getSourceClass(),
                ];
            }
        }

        /** @var FileDiff[] $diffs */
        foreach ($this->errorAndDiffCollector->getFileDiffs() as $file => $diffs) {
            foreach ($diffs as $diff) {
                $errorsArray['files'][$file]['diffs'][] = [
                    'diff' => $diff->getDiff(),
                    'appliedCheckers' => $diff->getAppliedCheckers(),
                ];
            }
        }

        $output->writeln(Json::encode($errorsArray, Json::PRETTY));

        return $errorsArray['totals']['errors'] === 0 ? ShellCode::SUCCESS : ShellCode::ERROR;
    }

    public function getName(): string
    {
        return Option::JSON_OUTPUT_FORMAT;
    }
}