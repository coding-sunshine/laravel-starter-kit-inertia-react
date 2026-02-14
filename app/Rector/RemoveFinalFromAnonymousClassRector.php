<?php

declare(strict_types=1);

namespace App\Rector;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Class_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Removes `final` from anonymous classes (invalid in PHP &lt; 8.3).
 */
final class RemoveFinalFromAnonymousClassRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove final modifier from anonymous classes for PHP compatibility',
            [
                new CodeSample(
                    'return new final class extends Migration {}',
                    'return new class extends Migration {}'
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [New_::class];
    }

    /**
     * @param  New_  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node->class instanceof Class_) {
            return null;
        }

        $class = $node->class;
        if (! $class->isAnonymous() || ! $class->isFinal()) {
            return null;
        }

        $class->flags &= ~Modifiers::FINAL;

        return $node;
    }
}
