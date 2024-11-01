<?php

namespace Datacom\LgkStore\Model;

class Constants
{
    const GROUP_ID_AZIENDA_ITALIANA = 9;
    const GROUP_ID_AZIENDA_ESTERA = 10;
    const GROUP_ID_PRIVATO_ITALIA = 8;
    const GROUP_ID_PRIVATO_CEE = 7;
    const GROUP_ID_PRIVATO_EXTRA_CEE = 12;
    const GROUP_ID_RIVENDITORE_ITALIA = 4;
    const GROUP_ID_RIVENDITORE_ESTERO = 5;

    const CUSTOM_REFERER_QUERY_PARAM = 'referer';

    const TABLE_QUOTE_ATTRIBUTES = 'dtm_quote_attributes';

    const PATH_PRODUCT_DOCS = 'pub'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'#STORECODE#'.DIRECTORY_SEPARATOR;
}