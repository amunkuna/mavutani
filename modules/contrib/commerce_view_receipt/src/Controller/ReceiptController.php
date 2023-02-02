<?php

namespace Drupal\commerce_view_receipt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Receipt Controller
 */
class ReceiptController extends ControllerBase {

  /** @var \Drupal\commerce_order\OrderTotalSummaryInterface */
  protected $orderTotalSummary;

  /**
   * Constructor
   */
  public function __construct(OrderTotalSummaryInterface $order_total_summary) {
    $this->orderTotalSummary = $order_total_summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_order.order_total_summary')
    );
  }

  /**
   * View receipt in the browser
   */
  public function viewReceipt(OrderInterface $commerce_order) {
    $billing_profile = $commerce_order->getBillingProfile();
    return [
      '#theme' => 'commerce_order_receipt',
      '#order_entity' => $commerce_order,
      '#billing_information' => $billing_profile ? $this
        ->entityTypeManager()
        ->getViewBuilder('profile')
        ->view($billing_profile) : NULL,
      '#totals' => $this
        ->orderTotalSummary
        ->buildTotals($commerce_order)
    ];
  }

}

