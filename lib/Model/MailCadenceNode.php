<?php
namespace Reacon\Sdk\Model;
/** Generated typed union; select a native branch model. */
final class MailCadenceNode extends ObjectUnion
{
    protected const VARIANTS = [
        ['class' => 'Reacon\\Sdk\\Model\\MailCadenceNodeAnyOf', 'empty' => false, 'closed' => false, 'keys' => ['id', 'name', 'kind', 'nextNodeId'], 'required' => ['id', 'name', 'kind', 'nextNodeId'], 'tags' => ['kind' => 'start']],
        ['class' => 'Reacon\\Sdk\\Model\\MailCadenceNodeAnyOf1', 'empty' => false, 'closed' => false, 'keys' => ['id', 'name', 'channel', 'experiment', 'kind', 'nextNodeId', 'templateId', 'templateVersion'], 'required' => ['id', 'name', 'channel', 'kind', 'nextNodeId', 'templateId', 'templateVersion'], 'tags' => ['kind' => 'message']],
        ['class' => 'Reacon\\Sdk\\Model\\MailCadenceNodeAnyOf2', 'empty' => false, 'closed' => false, 'keys' => ['id', 'name', 'durationMs', 'kind', 'nextNodeId'], 'required' => ['id', 'name', 'durationMs', 'kind', 'nextNodeId'], 'tags' => ['kind' => 'wait']],
        ['class' => 'Reacon\\Sdk\\Model\\MailCadenceNodeAnyOf3', 'empty' => false, 'closed' => false, 'keys' => ['id', 'name', 'kind', 'nextNodeId', 'taskType', 'title'], 'required' => ['id', 'name', 'kind', 'nextNodeId', 'taskType', 'title'], 'tags' => ['kind' => 'task']],
        ['class' => 'Reacon\\Sdk\\Model\\MailCadenceNodeAnyOf4', 'empty' => false, 'closed' => false, 'keys' => ['id', 'name', 'condition', 'falseNodeId', 'kind', 'trueNodeId'], 'required' => ['id', 'name', 'condition', 'falseNodeId', 'kind', 'trueNodeId'], 'tags' => ['kind' => 'branch']],
        ['class' => 'Reacon\\Sdk\\Model\\MailCadenceNodeAnyOf5', 'empty' => false, 'closed' => false, 'keys' => ['id', 'name', 'experiment', 'kind'], 'required' => ['id', 'name', 'experiment', 'kind'], 'tags' => ['kind' => 'experiment_split']],
        ['class' => 'Reacon\\Sdk\\Model\\MailCadenceNodeAnyOf6', 'empty' => false, 'closed' => false, 'keys' => ['id', 'name', 'kind', 'outcome'], 'required' => ['id', 'name', 'kind', 'outcome'], 'tags' => ['kind' => 'stop']],
    ];
    public function __construct(MailCadenceNodeAnyOf|MailCadenceNodeAnyOf1|MailCadenceNodeAnyOf2|MailCadenceNodeAnyOf3|MailCadenceNodeAnyOf4|MailCadenceNodeAnyOf5|MailCadenceNodeAnyOf6 $value) { parent::__construct($value); }
}
