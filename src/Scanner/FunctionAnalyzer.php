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
                }

                if (is_numeric($token[2])) {
                    $line = $token[2];
                }

                // Analyze function names and coverage
                if ($isFunction && $token[0] === T_STRING) {
                    $functionName = $token[1];
                    $hasDoc = $doc !== null;

                    // Simple heuristic: check if the next tokens have a colon (:) for return type
                    $hasType = false;

                    for ($j = $i; $j < count($tokens); $j++) {
                        $nextToken = $tokens[$j];

                        if (is_array($nextToken)) {
                            // Skip whitespace/comments
                            if (in_array($nextToken[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                                continue;
                            }
                        }

                        if ($nextToken === '(') {
                            // We've reached the parameter list, continue until closing )
                            $parenCount = 1;
                            $j++;
                            while ($j < count($tokens) && $parenCount > 0) {
                                if ($tokens[$j] === '(') {
                                    $parenCount++;
                                } elseif ($tokens[$j] === ')') {
                                    $parenCount--;
                                }
                                $j++;
                            }

                            // Now look ahead for colon and return type
                            while ($j < count($tokens)) {
                                $token = $tokens[$j];

                                if (is_array($token) && in_array($token[0], [T_WHITESPACE])) {
                                    $j++;

                                    continue;
                                }

                                if ($token === ':') {
                                    // Found return type declaration
                                    $hasType = true;
                                    break;
                                }

                                // No colon = no return type
                                break;
                            }

                            break;
                        }
                    }

                    // Add results based on level
                    if (
                        ($level === self::LEVEL_BASIC && !$hasDoc) ||
                        ($level === self::LEVEL_STRICT && (!$hasDoc || !$hasType))
                    ) {
                        $results[] = [
                            'function' => $functionName,
                            'has_doc' => $hasDoc,
                            'has_type' => $hasType,
                            'line' => $line,
                        ];
                    }

                    // Reset state for the next function
                    $doc = null;
                    $isFunction = false;
                    $line = null;
                }
            }
        }

        return $results;
    }
}
