<?php

namespace lexisother\Composer\Patcher;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Patch;
use cweagans\Composer\Patcher\PatcherBase;

class RegexPatcher extends PatcherBase
{
    public array $options;
    public bool $__REGEXPATCHER__ = true;

    public function apply(Patch $patch, string $path): bool
    {
        $this->options = $regexOptions = $patch->extra['regex'];
        if (empty($regexOptions)) {
            return $this->failWithReason('Required configuration for RegexPatcher not present. Skipping.');
        }

        if (isset($regexOptions['fromUrl']) && $regexOptions['fromUrl']) {
            $patchesJson = json_decode(file_get_contents($patch->localPath), true);
            return $this->processPatches($patchesJson, $path);
        } else {
            if (!isset($regexOptions['files'])) {
                return $this->failWithReason("RegexPatcher option fromUrl not present or false, but no files key present. Skipping.");
            }

            return $this->processPatches($regexOptions['files'], $path);
        }
    }

    public function canUse(): bool
    {
        // Hardcoded to true because we're using regular PHP functionality for patching.
        return true;
    }

    private function processPatches(array $patchFiles, string $path): bool
    {
        $errors = [];
        foreach ($patchFiles as $file => $patches) {
            // In case this is present but false
            if ($file === "fromUrl") continue;

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

                $found = preg_match_all($patch['find'], $contents);
                if ($found === 0) {
                    $errors[$file][] = "Find of patch $i found nothing.";
                }

                $contents = preg_replace($patch['find'], $patch['replace'], $contents, $limit);
                if ($contents === null) {
                    $errors[$file][] = "Replacement of patch $i returned null.";
                }

                // TODO: Compose all changes and write only once
                if (count($errors[$file]) === 0) {
                    $this->io->write("      - Writing out <info>$file</info> (patch $i)");
                    file_put_contents("$path/$file", $contents);
                }
            }
        }

        $wereThereErrors = false;
        if (count($errors) > 0) {
            foreach ($errors as $file => $fileErrors) {
                if (count($fileErrors) > 0) {
                    if (!isset($this->options['ignoreErrors']) || !$this->options['ignoreErrors'])
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

    private function failWithReason(string $reason)
    {
        $this->io->write(
            $reason,
            true,
            IOInterface::VERBOSE
        );
        return false;
    }
}
