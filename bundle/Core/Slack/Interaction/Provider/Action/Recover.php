<?php
/**
 * NovaeZSlackBundle Bundle.
 *
 * @package   Novactive\Bundle\eZSlackBundle
 *
 * @author    Novactive <s.morel@novactive.com>
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/NovaeZSlackBundle/blob/master/LICENSE MIT Licence
 */
declare(strict_types=1);

namespace Novactive\Bundle\eZSlackBundle\Core\Slack\Interaction\Provider\Action;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Query as eZQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\TrashItem;
use eZ\Publish\Core\SignalSlot\Signal;
use Novactive\Bundle\eZSlackBundle\Core\Slack\Action;
use Novactive\Bundle\eZSlackBundle\Core\Slack\Attachment;
use Novactive\Bundle\eZSlackBundle\Core\Slack\Button;
use Novactive\Bundle\eZSlackBundle\Core\Slack\Confirmation;
use Novactive\Bundle\eZSlackBundle\Core\Slack\InteractiveMessage;

/**
 * Class Recover.
 */
class Recover extends ActionProvider
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * Hide constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(Signal $signal, int $index): ?Action
    {
        if (!$signal instanceof Signal\TrashService\TrashSignal) {
            return null;
        }
        $value  = $signal->contentId;
        $button = new Button($this->getAlias(), '_t:action.recover', (string) $value);
        $button->setStyle(Button::PRIMARY_STYLE);
        $confirmation = new Confirmation('_t:action.generic.confirmation');
        $button->setConfirmation($confirmation);

        return $button;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InteractiveMessage $message): Attachment
    {
        $action     = $message->getAction();
        $value      = (int) $action->getValue();
        $attachment = new Attachment();
        $attachment->setTitle('_t:action.recover');
        try {
            $query         = new eZQuery();
            $query->filter = new Criterion\ContentId($value);
            $results       = $this->repository->getTrashService()->findTrashItems($query);
            foreach ($results as $item) {
                /* @var TrashItem $item */
                $this->repository->getTrashService()->recover($item);
            }
            $attachment->setColor('good');
            $attachment->setText('_t:action.items.recovered');
        } catch (\Exception $e) {
            $attachment->setColor('danger');
            $attachment->setText($e->getMessage());
        }

        return $attachment;
    }
}