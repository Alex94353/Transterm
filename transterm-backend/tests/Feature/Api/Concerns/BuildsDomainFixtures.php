<?php

namespace Tests\Feature\Api\Concerns;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Language;
use App\Models\LanguagePair;

trait BuildsDomainFixtures
{
    /**
     * @return array{source: Language, target: Language, fieldGroup: FieldGroup, field: Field, pair: LanguagePair}
     */
    protected function createLanguagePairAndField(): array
    {
        $suffix = (string) microtime(true);

        $source = Language::create([
            'name' => "Source {$suffix}",
            'code' => 's'.substr(md5("source-{$suffix}"), 0, 8),
        ]);

        $target = Language::create([
            'name' => "Target {$suffix}",
            'code' => 't'.substr(md5("target-{$suffix}"), 0, 8),
        ]);

        $fieldGroup = FieldGroup::create([
            'name' => "Group {$suffix}",
            'code' => 'g'.substr(md5("group-{$suffix}"), 0, 6),
        ]);

        $field = Field::create([
            'field_group_id' => $fieldGroup->id,
            'name' => "Field {$suffix}",
            'code' => 'f'.substr(md5("field-{$suffix}"), 0, 6),
        ]);

        $pair = LanguagePair::create([
            'source_language_id' => $source->id,
            'target_language_id' => $target->id,
        ]);

        return compact('source', 'target', 'fieldGroup', 'field', 'pair');
    }
}
