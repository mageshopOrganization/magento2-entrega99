<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Controller\Adminhtml\Waypoint;

use MageShop\Entrega99\Controller\Adminhtml\Waypoint;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends Waypoint implements HttpPostActionInterface
{
    public function execute(): ResultInterface
    {
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/');
        }

        $waypointId = isset($data['waypoint_id']) ? (int)$data['waypoint_id'] : 0;

        try {
            $waypoint = $waypointId
                ? $this->waypointRepository->getById($waypointId)
                : $this->waypointFactory->create();

            // Normalize a few fields
            if (isset($data['active'])) {
                $data['active'] = (bool)$data['active'];
            }
            if (isset($data['store_id']) && $data['store_id'] === '') {
                $data['store_id'] = null;
            }
            if (isset($data['msi_source_code']) && $data['msi_source_code'] === '') {
                $data['msi_source_code'] = null;
            }
            foreach (['latitude', 'longitude'] as $coord) {
                if (isset($data[$coord]) && $data[$coord] === '') {
                    $data[$coord] = null;
                }
            }

            $waypoint->setData(array_merge($waypoint->getData(), $data));
            $this->waypointRepository->save($waypoint);

            $this->messageManager->addSuccessMessage(__('Pickup location saved.'));

            if ($this->getRequest()->getParam('back') === 'edit') {
                return $redirect->setPath('*/*/edit', ['waypoint_id' => $waypoint->getWaypointId()]);
            }
            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This waypoint no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not save: %1', $e->getMessage()));
            return $redirect->setPath('*/*/edit', ['waypoint_id' => $waypointId]);
        }
    }
}
