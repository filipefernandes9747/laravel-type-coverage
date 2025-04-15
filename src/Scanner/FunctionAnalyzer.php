<?php

namespace Filipefernandes\LaravelTypeCoverage\Scanner;

class FunctionAnalyzer
{

    /**
     * Analyze a PHP file for function coverage.
     *
     * @param string $filePath Path to the PHP file.
     * @return array List of functions with their coverage status.
     */
    public static function analyze(string $filePath): array
    {
        $code = file_get_contents($filePath);
        $tokens = token_get_all($code);

        $results = [];
        $doc = null;
        $isFunction = false;

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                if ($token[0] === T_DOC_COMMENT) {
                    $doc = $token[1];
                }

                if ($token[0] === T_FUNCTION) {
                    $isFunction = true;
                }

                if ($isFunction && $token[0] === T_STRING) {
                    $functionName = $token[1];
                    $hasDoc = $doc !== null;

                    // Simple heuristic: check if next tokens have colon (:) for return type
                    $hasType = false;
                    for ($j = $i; $j < $i + 10 && isset($tokens[$j]); $j++) {
                        if (is_array($tokens[$j])) continue;
                        if ($tokens[$j] === ':') {
                            $hasType = true;
                            break;
                        }
                    }

                    $results[] = [
                        'function' => $functionName,
                        'has_doc' => $hasDoc,
                        'has_type' => $hasType,
                    ];

                    $doc = null;
                    $isFunction = false;
                }
            }
        }

        return $results;
    }
}
