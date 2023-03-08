<?php

namespace Datacom\LgkStore\Plugin\Quote\Model;

class ShippingMethodManagement {
    public function afterEstimateByExtendedAddress($shippingMethodManagement, $output) {
        return $output;
        //return $this->filterOutput($output);
    }

    private function filterOutput($output) {
        $free = [];
        foreach ($output as $shippingMethod) {
            if ($shippingMethod->getCarrierCode() == 'freeshipping' && $shippingMethod->getMethodCode() == 'freeshipping') {
                $free[] = $shippingMethod;
            }
        }

        if (!empty($free)) {
            return $free;
        }

        return $output;
    }
}