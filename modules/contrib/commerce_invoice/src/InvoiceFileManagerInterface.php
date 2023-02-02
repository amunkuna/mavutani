<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;

/**
 * Manages the invoice file.
 */
interface InvoiceFileManagerInterface {

  /**
   * Gets the file for an invoice.
   *
   * If the file does not exist, a new PDF file is generated, and the
   * reference field on the invoice is set.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice, NULL if not found.
   *
   * @return \Drupal\file\FileInterface|null
   *   The invoice file, NULL if the invoice could not be generated.
   */
  public function getInvoiceFile(InvoiceInterface $invoice);

}
