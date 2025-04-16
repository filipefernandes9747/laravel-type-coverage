<?php

namespace Filipefernandes\LaravelTypeCoverage\Scanner;

class FunctionAnalyzer
{
    const LEVEL_NONE = 0; // No checks

    const LEVEL_BASIC = 1; // Check doc comments

    const LEVEL_STRICT = 2; // Check doc comments and return types

    /**
     * Analyze a PHP file for function coverage with a given strictness level.
     *
     * @param  string  $filePath  Path to the PHP file.
     * @param  int  $level  Level of strictness (0: none, 1: basic, 2: strict).
     * @return array List of functions with their coverage status.
     */
    public static function analyze(string $filePath, int $level = self::LEVEL_BASIC): array
    {
        $code = file_get_contents($filePath);
        $tokens = token_get_all($code);

        $results = [];
        $doc = null;
        $isFunction = false;
        $isClosure = false;
        $line = null;

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                // Capture doc comments
                if ($token[0] === T_DOC_COMMENT) {
                    $doc = $token[1];
                }

                // Identify functions
                if ($token[0] === T_FUNCTION) {
                    $isFunction = true;

                    // Look ahead to see if it's a named function or a closure
                    $k = $i + 1;
                    while (isset($tokens[$k]) && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                        $k++;
                    }

                    $isClosure = true;

                    if (isset($tokens[$k]) && is_array($tokens[$k]) && $tokens[$k][0] === T_STRING) {
                        $isClosure = false;
                    }
                }


                if (is_numeric($token[2])) {
                    $line = $token[2];
                }

                // Analyze function names and coverage
                if ($isFunction && $token[0] === T_STRING) {
                    $functionName = $token[1];
                    $hasDoc = !$isClosure && $doc !== null;

                    // Simple heuristic: check if the next tokens have a colon (:) for return type
                    $hasType = false;
                    if ($level >= self::LEVEL_STRICT) {
                        for ($j = $i; $j < $i + 10 && isset($tokens[$j]); $j++) {
                            if (is_array($tokens[$j])) continue;
                            if ($tokens[$j] === ':') {
                                $hasType = true;
                                break;
                            }
                        }
                    }

                    // Add results based on level
                    $results[] = [
                        'function' => $functionName,
                        'has_doc' => $hasDoc,
                        'has_type' => $hasType,
                        'is_closure' => $isClosure,
                        'line' => $line,
                    ];


                    // Reset state for the next function
                    $doc = null;
                    $isFunction = false;
                    $isClosure = false;
                    $line = null;
                }
            }
        }

        return $results;
    }
}
