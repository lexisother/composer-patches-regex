<?php

namespace lexisother\Composer\Patcher;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Patch;
use cweagans\Composer\Patcher\PatcherBase;

class RegexPatcher extends PatcherBase
{
    public bool $__REGEXPATCHER__ = true;

    public function apply(Patch $patch, string $path): bool
    {
        $regexOptions = $patch->extra['regex'];
        if (empty($regexOptions)) {
            $this->io->write(
                'Required configuration for RegexPatcher not present. Skipping.',
                true,
                IOInterface::VERBOSE
            );
            return false;
        }

        $errors = [];
        foreach ($regexOptions as $file => $patches) {
            $errors[$file] = [];

            foreach ($patches as $i => $patch) {
                if (!isset($patch['find']) || !isset($patch['replace'])) {
                    $errors[$file][] = "Patch $i is missing a find or replace.";
                    continue;
                }

                // Let's just enforce adding the `g` flag to the expression
                $limit = -1;
                if (str_ends_with($patch['find'], 'g')) {
                    $patch['find'] = rtrim($patch['find'], 'g');
                } else {
                    $limit = 1;
                }

                $contents = file_get_contents("$path/$file");

                $matcher = $limit === -1 ? preg_match_all(...) : preg_match(...);
                $found = $matcher($patch['find'], $contents);
                if ($found !== 1) {
                    $errors[$file][] = "Find of patch $i found nothing.";
                }

                $contents = preg_replace($patch['find'], $patch['replace'], $contents, $limit);
                if ($contents === null) {
                    $errors[$file][] = "Replacement of patch $i returned null.";
                }

                if (count($errors[$file]) === 0) {
                    $this->io->write("      - Writing out <info>$file</info>");
                    file_put_contents("$path/$file", $contents);
                }
            }
        }


        $wereThereErrors = false;
        if (count($errors) > 0) {
            foreach ($errors as $file => $fileErrors) {
                if (count($fileErrors) > 0) {
                    $wereThereErrors = true;

                    $this->io->error("      - Errors in $file:");
                    foreach ($fileErrors as $error) {
                        $this->io->error("            $error");
                    }
                }
            }
        }

        return !$wereThereErrors;
    }

    public function canUse(): bool
    {
        // Hardcoded to true because we're using regular PHP functionality for patching.
        return true;
    }
}
