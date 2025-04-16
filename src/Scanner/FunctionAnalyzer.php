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
    public static function analyze(string $filePath, int $level = self::LEVEL_BASIC, array $excluded = []): array
    {
        $code = file_get_contents($filePath);
        $tokens = token_get_all($code, TOKEN_PARSE);
        if ($tokens === false) {
            throw new \RuntimeException("Failed to tokenize the file: $filePath");
        }
        $results = [];
        $doc = null;
        $isFunction = false;
        $isClosure = true;
        $line = null;
        $functionName = null;
        $hasFoundFunction = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if (is_array($token)) {
                // Capture doc comments
                if ($token[0] === T_DOC_COMMENT) {
                    $doc = $token[1];
                }

                // Track line numbers
                if (is_numeric($token[2])) {
                    $line = $token[2];
                }

                // Identify functions
                if ($token[0] === T_FUNCTION) {
                    $isFunction = true;
                    $hasFoundFunction = true;

                    $k = $i + 1;
                    $isClosure = true;

                    while (isset($tokens[$k])) {
                        $next = $tokens[$k];

                        if (is_array($next)) {
                            if ($next[0] === T_WHITESPACE) {
                                $k++;

                                continue;
                            }

                            // If the next significant token is T_STRING, it's a named function
                            if ($next[0] === T_STRING) {
                                $isClosure = false;
                                $functionName = $next[1];
                            }
                        }
                        break;
                    }

                    // If it's a closure, ignore any doc block collected before
                    if ($isClosure) {
                        $doc = null;
                    }
                }
            }

            // Check for return type declaration
            // Need to find pattern: function name(...) : return_type
            if ($hasFoundFunction && !$isClosure && $functionName) {
                $hasDoc = $doc !== null;
                $hasType = false;

                if ($level >= self::LEVEL_STRICT) {
                    // Find the closing parenthesis and check if it's followed by a colon
                    $openParens = 0;
                    $foundCloseParen = false;

                    for ($j = $i; $j < count($tokens); $j++) {
                        $currentToken = $tokens[$j];

                        // Count parentheses to find the matching closing parenthesis
                        if (!is_array($currentToken)) {
                            if ($currentToken === '(') {
                                $openParens++;
                            } elseif ($currentToken === ')') {
                                $openParens--;
                                if ($openParens === 0) {
                                    $foundCloseParen = true;

                                    // Look for colon after the closing parenthesis
                                    for ($k = $j + 1; $k < $j + 10 && $k < count($tokens); $k++) {
                                        if (is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                                            continue;
                                        }

                                        if (!is_array($tokens[$k]) && $tokens[$k] === ':') {
                                            $hasType = true;
                                            break;
                                        }

                                        // If we hit a token that's not whitespace and not a colon, break
                                        if (!(is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE)) {
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }
                        }

                        // If we hit a semicolon or opening curly brace before finding the matching parenthesis,
                        // we've gone too far
                        if (!is_array($currentToken) && ($currentToken === ';' || $currentToken === '{')) {
                            break;
                        }
                    }
                }

                $excludedFunctionNames = array_merge([
                    '__construct'
                ], $excluded);

                // Add results
                if (!in_array($functionName, $excludedFunctionNames)) {
                    $results[] = [
                        'function' => $functionName,
                        'has_doc' => $hasDoc,
                        'has_type' => $hasType,
                        'is_closure' => $isClosure,
                        'line' => $line,
                    ];
                }

                // Reset state for the next function
                $doc = null;
                $isFunction = false;
                $isClosure = false;
                $line = null;
                $functionName = null;
                $hasFoundFunction = false;
            }
        }

        return $results;
    }
}
