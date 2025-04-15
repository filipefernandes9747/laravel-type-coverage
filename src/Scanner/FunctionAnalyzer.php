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
     * @param string $filePath Path to the PHP file.
     * @param int $level Level of strictness (0: none, 1: basic, 2: strict).
     * @return array List of functions with their coverage status.
     */
    public static function analyze(string $filePath, int $level = self::LEVEL_BASIC): array
    {
        $code = file_get_contents($filePath);
        $tokens = token_get_all($code);

        $results = [];
        $doc = null;
        $isFunction = false;

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                // Capture doc comments
                if ($token[0] === T_DOC_COMMENT) {
                    $doc = $token[1];
                }

                // Identify functions
                if ($token[0] === T_FUNCTION) {
                    $isFunction = true;
                }

                // Analyze function names and coverage
                if ($isFunction && $token[0] === T_STRING) {
                    $functionName = $token[1];
                    $hasDoc = $doc !== null;

                    $hasType = false;

                    // Look ahead for a colon followed by a return type
                    for ($j = $i; $j < $i + 10 && isset($tokens[$j]); $j++) {
                        $current = $tokens[$j];

                        // Ensure it's not an array token (like whitespace or comments)
                        if (is_array($current)) {
                            continue;
                        }

                        // Check for colon (indicating return type declaration)
                        if ($current === ':') {
                            // Now peek ahead for the return type
                            for ($k = $j + 1; $k < $j + 5 && isset($tokens[$k]); $k++) {
                                $returnToken = $tokens[$k];
                                if (is_array($returnToken)) {
                                    if (in_array($returnToken[0], [T_STRING, T_NAME_QUALIFIED, T_STATIC, T_ARRAY, T_CALLABLE])) {
                                        $hasType = true;
                                        break 2; // break both loops
                                    }
                                } elseif ($returnToken === '?' || $returnToken === '\\') {
                                    // nullable type or namespaced type
                                    $hasType = true;
                                    break 2;
                                }
                            }
                        }
                    }

                    // Add results based on level
                    $results[] = [
                        'function' => $functionName,
                        'has_doc' => $hasDoc,
                        'has_type' => $hasType,
                        'line' => $token[2] ?? null,
                    ];

                    // Reset state for the next function
                    $doc = null;
                    $isFunction = false;
                }
            }
        }

        return $results;
    }
}
