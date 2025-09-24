<?php declare(strict_types=1);

namespace EbicsApi\Ebics\Builders\Document;

use DateTime;
use EbicsApi\Ebics\Contracts\PostalAddressInterface;
use EbicsApi\Ebics\Models\CustomerCreditTransfer;
use InvalidArgumentException;

/**
 * CustomerCreditTransferBuilder
 * Corresponds to EbicsApi\Ebics\Builders\CustomerCreditTransfer\CustomerCreditTransferBuilder but with namespaces
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Jan-Philipp Georg (moori)
 */
final class CustomerUrgentCreditTransferBuilder extends DocumentBuilder
{
    public function createInstance(
        string $debitorFinInstBIC,
        string $debitorIBAN,
        string $debitorName,
        ?DateTime $executionDate = null,
        bool $batchBooking = true,
        ?string $msgId = null,
        ?string $paymentReference = null,
        ?string $chargeBearer = null
    ): self {
        $this->instance = new CustomerCreditTransfer();
        $now = new DateTime();

        $doc = $this->el('Document');
        $doc->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $doc->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            $this->schemaLocation()
        );
        $this->instance->appendChild($doc);

        $ccti = $this->el('CstmrCdtTrfInitn');
        $doc->appendChild($ccti);

        $grpHdr = $this->el('GrpHdr');
        $ccti->appendChild($grpHdr);
        $grpHdr->appendChild(
            $this->el('MsgId', $msgId ?: $this->randomService->uniqueIdWithDate('msg'))
        );
        $grpHdr->appendChild($this->el('CreDtTm', $now->format('Y-m-d\TH:i:s\Z')));
        $grpHdr->appendChild($this->el('NbOfTxs', '0'));
        $grpHdr->appendChild($this->el('CtrlSum', '0'));

        $initg = $this->el('InitgPty');
        $initg->appendChild($this->el('Nm', $debitorName));
        $grpHdr->appendChild($initg);

        $pmtInf = $this->el('PmtInf');
        $ccti->appendChild($pmtInf);

        $pmtInf->appendChild(
            $this->el('PmtInfId', $paymentReference ?: $this->randomService->uniqueIdWithDate('pmt'))
        );
        $pmtInf->appendChild($this->el('PmtMtd', 'TRF'));
        $pmtInf->appendChild($this->el('BtchBookg', $batchBooking ? 'true' : 'false'));
        $pmtInf->appendChild($this->el('NbOfTxs', '0'));
        $pmtInf->appendChild($this->el('CtrlSum', '0'));

        $pmtTpInf = $this->el('PmtTpInf');
        $svcLvl = $this->el('SvcLvl');
        $svcLvl->appendChild($this->el('Cd', 'SEPA'));
        $pmtTpInf->appendChild($svcLvl);
        $pmtInf->appendChild($pmtTpInf);

        $pmtInf->appendChild($this->buildReqdExctnDt($executionDate ?: $now));

        $dbtr = $this->el('Dbtr');
        $dbtr->appendChild($this->el('Nm', $debitorName));
        $pmtInf->appendChild($dbtr);

        $dbtrAcct = $this->el('DbtrAcct');
        $id = $this->el('Id');
        $id->appendChild($this->el('IBAN', $debitorIBAN));
        $dbtrAcct->appendChild($id);
        $pmtInf->appendChild($dbtrAcct);

        $dbtrAgt = $this->el('DbtrAgt');
        $fin = $this->el('FinInstnId');
        $fin->appendChild($this->el('BICFI', $debitorFinInstBIC));
        $dbtrAgt->appendChild($fin);
        $pmtInf->appendChild($dbtrAgt);

        $pmtInf->appendChild($this->el('ChrgBr', $chargeBearer ?: 'SLEV'));

        return $this;
    }

    public function addBankTransaction(
        string $creditorIBAN,
        string $creditorName,
        ?PostalAddressInterface $postalAddress,
        float $amount,
        string $currency,
        ?string $purposeText = null,
        ?string $endToEndId = null,
        ?string $purposeCode = null
    ): self {
        if ($currency !== 'EUR') {
            throw new InvalidArgumentException('The SEPA transaction is restricted to EUR currency.');
        }

        $tx = $this->createCreditTransferTransactionElement($amount, null, $endToEndId);
        $this->addAmountElement($tx, $amount, $currency);
        $this->addCreditor(
            $tx,
            null,
            $creditorIBAN,
            $creditorName,
            $postalAddress,
            $purposeText,
            $purposeCode
        );

        return $this;
    }

    public function addSEPATransaction(
        string $creditorFinInstBIC,
        string $creditorIBAN,
        string $creditorName,
        ?PostalAddressInterface $postalAddress,
        float $amount,
        string $currency,
        ?string $purposeText = null,
        ?string $chargeBearer = null,
        ?string $endToEndId = null,
        ?string $purposeCode = null
    ): self {
        if ($currency !== 'EUR') {
            throw new InvalidArgumentException('The SEPA transaction is restricted to EUR currency.');
        }

        $tx = $this->createCreditTransferTransactionElement($amount, null, $endToEndId);

        $this->addAmountElement($tx, $amount, $currency);

        if ($chargeBearer !== null) {
            $tx->appendChild($this->el('ChrgBr', $chargeBearer));
        }

        $this->addCreditor(
            $tx,
            $creditorFinInstBIC,
            $creditorIBAN,
            $creditorName,
            $postalAddress,
            $purposeText,
            $purposeCode
        );

        return $this;
    }

    public function addForeignTransaction(
        string $creditorFinInstBIC,
        string $creditorIBAN,
        string $creditorName,
        ?PostalAddressInterface $postalAddress,
        float $amount,
        string $currency,
        ?string $purposeText = null,
        ?string $endToEndId = null,
        ?string $purposeCode = null
    ): self {
        $tx = $this->createCreditTransferTransactionElement($amount, null, $endToEndId);
        $this->addAmountElement($tx, $amount, $currency);
        $this->addCreditor(
            $tx,
            $creditorFinInstBIC,
            $creditorIBAN,
            $creditorName,
            $postalAddress,
            $purposeText,
            $purposeCode
        );

        return $this;
    }
}
