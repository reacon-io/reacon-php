<?php
namespace Reacon\Sdk\Model;
/** Generated typed union; select a native branch model. */
final class MailPostCadencesRequestNodesInner extends ObjectUnion
{
    protected const VARIANTS = [
        ['class' => 'Reacon\\Sdk\\Model\\MailPostCadencesRequestNodesInnerAnyOf', 'empty' => false, 'closed' => true, 'keys' => ['id', 'kind', 'name', 'nextNodeId'], 'required' => ['id', 'name', 'kind', 'nextNodeId'], 'tags' => ['kind' => 'start']],
        ['class' => 'Reacon\\Sdk\\Model\\MailPostCadencesRequestNodesInnerAnyOf1', 'empty' => false, 'closed' => true, 'keys' => ['channel', 'experiment', 'id', 'kind', 'name', 'nextNodeId', 'templateId', 'templateVersion'], 'required' => ['id', 'name', 'kind', 'channel', 'templateId', 'templateVersion', 'nextNodeId'], 'tags' => ['kind' => 'message']],
        ['class' => 'Reacon\\Sdk\\Model\\MailPostCadencesRequestNodesInnerAnyOf2', 'empty' => false, 'closed' => true, 'keys' => ['durationMs', 'id', 'kind', 'name', 'nextNodeId'], 'required' => ['id', 'name', 'kind', 'durationMs', 'nextNodeId'], 'tags' => ['kind' => 'wait']],
        ['class' => 'Reacon\\Sdk\\Model\\MailPostCadencesRequestNodesInnerAnyOf3', 'empty' => false, 'closed' => true, 'keys' => ['id', 'kind', 'name', 'nextNodeId', 'taskType', 'title'], 'required' => ['id', 'name', 'kind', 'taskType', 'title', 'nextNodeId'], 'tags' => ['kind' => 'task']],
        ['class' => 'Reacon\\Sdk\\Model\\MailPostCadencesRequestNodesInnerAnyOf4', 'empty' => false, 'closed' => true, 'keys' => ['condition', 'falseNodeId', 'id', 'kind', 'name', 'trueNodeId'], 'required' => ['id', 'name', 'kind', 'condition', 'trueNodeId', 'falseNodeId'], 'tags' => ['kind' => 'branch']],
        ['class' => 'Reacon\\Sdk\\Model\\MailPostCadencesRequestNodesInnerAnyOf5', 'empty' => false, 'closed' => true, 'keys' => ['experiment', 'id', 'kind', 'name'], 'required' => ['id', 'name', 'kind', 'experiment'], 'tags' => ['kind' => 'experiment_split']],
        ['class' => 'Reacon\\Sdk\\Model\\MailPostCadencesRequestNodesInnerAnyOf6', 'empty' => false, 'closed' => true, 'keys' => ['id', 'kind', 'name', 'outcome'], 'required' => ['id', 'name', 'kind', 'outcome'], 'tags' => ['kind' => 'stop']],
    ];
    public function __construct(MailPostCadencesRequestNodesInnerAnyOf|MailPostCadencesRequestNodesInnerAnyOf1|MailPostCadencesRequestNodesInnerAnyOf2|MailPostCadencesRequestNodesInnerAnyOf3|MailPostCadencesRequestNodesInnerAnyOf4|MailPostCadencesRequestNodesInnerAnyOf5|MailPostCadencesRequestNodesInnerAnyOf6 $value) { parent::__construct($value); }
}
