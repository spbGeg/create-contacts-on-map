<?php
namespace VadimRomanov;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Iblock;

class HelperIblock{

	private static $_istance = null;

    public $iblock = null;

	public static function getInstance(){
		if(is_null(self::$_istance)){
			self::$_istance = new self();
		}

		return self::$_istance;
	}

	/**
	 * @param $iblockCode
	 *
	 * @return mixed
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public function getIBlock($iblockCode){
		if( ! Loader::IncludeModule("iblock")){
			throw new SystemException('iblock not installed');
		}

		$ilblock = \Bitrix\Iblock\IblockTable::getList([
				'filter' => ['=CODE' => $iblockCode],
			])->fetch();
		if( ! $ilblock){
			throw new \Exception('Не найден iblock '.$iblockCode);
		}

		$this->iblock = $ilblock;


		return $ilblock;
	}


	public function getTablePathIblock($iblockCode){
		$this->getIBlock($iblockCode);
		return \Bitrix\Iblock\Iblock::wakeUp($this->iblock['ID'])->getEntityDataClass();
	}

    /**
     * Возвращает ID инфоблока по его коду
     *
     * @param string $iblockCode
     * @param int    $cacheTime
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    static function getIblockIdByCode(string $iblockCode, int $cacheTime = 86400000): int
    {
        $iblock = \Bitrix\Iblock\IblockTable::getList([
            'filter' => [
                '=CODE' => $iblockCode
            ],
            'select' => ['ID'],
            'limit'  => 1,
            'cache'  => [
                'ttl' => $cacheTime
            ]
        ])->fetch();

        return ($iblock['ID'] > 0) ? $iblock['ID'] : 0;
    }

    /**
     * Create new iblock
     * @param $typeCode
     * @param $code
     * @param $name
     * @return array
     */
    protected function addIblock($typeCode, $code, $name)
    {
        $result = [];
        try {
            $ib = new \CIBlock;
            $arFields = array(
                "ACTIVE" => 'Y',
                "NAME" => !empty($name) ? $name : $code,
                "CODE" => $code,
                "API_CODE" => str_replace('_', '', $code),
                "IBLOCK_TYPE_ID" => $typeCode,
                "SITE_ID" => array("s1"),

            );
            $res = $ib->Add($arFields);
            if ($res) {
                $result['STATUS'] = 'success';
                $result['IBLOCK']['ID'] = $res;
            } else {
                $result['STATUS'] = 'fail';
                $result['ERROR'] = 'Не удалось добавить инфоблок';
            }
            return $result;

        } catch (\Exception $ib) {
            $result['ERROR'] = 'Ошибка: ' . $ib->LAST_ERROR . '<br>';
            return $result;
        }
    }

    /**
     * Find Iblock by code, return it ID
     *
     * @param $typeCode
     * @return array|null
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    public function issetTypeIblock($typeCode)
    {
        $result = \Bitrix\Iblock\TypeTable::getList([
            'select' => [
                'ID',
            ],
            'filter' => ['=ID' => $typeCode],
            'limit' => 1,
        ])->fetchAll();
        return array_shift($result);
    }

    /**
     * create typeIblock return array with status process
     *
     * @param $typeCode
     * @return array
     */
    public function addTypeIblock($typeCode)
    {

        $result = [
            'status' => false,
            'error' => false
        ];

        $arFields = array(
            'ID' => $typeCode['ID'],
            'SECTIONS' => 'N',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => array(
                'ru' => array(
                    'NAME' => $typeCode['NAME'],
                )
            )
        );

        $obBlocktype = new \CIBlockType;

        try {

            $res = $obBlocktype->Add($arFields);
            if ($res) {
                $result['STATUS'] = 'success';
            }

            return $result;
        } catch (\Exception $obBlocktype) {
            $result['ERROR'] = 'Error: ' . $obBlocktype->LAST_ERROR . '<br>';
            return $result;
        }
    }

    /**
     * Ib find iblock  return id
     *
     * @param $typeCode
     * @param $code
     * @return false|string
     */

    protected function findIblock($typeCode, $code)
    {
        $arIblock = \CIBlock::GetList(
            array(),
            array(
                'TYPE' => $typeCode,
                'CODE' => $code,
                'CHECK_PERMISSIONS' => 'N'
            )
        )->fetch();
        if (!empty($arIblock)) {
            return $arIblock['ID'];
        } else {
            return false;
        }
    }

    /**
     * Check is created prop in iblock, by first field
     *
     * @param $iblockId
     * @param $arCdodeProp
     * @return array
     */

    protected function isExistPropertyFields($iblockId, $arCdodeProp)
    {
        $result = [];
        $rsProperty = \CIBlockProperty::GetList(array(), array('IBLOCK_ID' => $iblockId, 'CODE' => $arCdodeProp));
        while ($element = $rsProperty->Fetch()) {
            $result[] = $element['CODE'];
        }
        return $result;
    }

    /**
     * Add property by iblock
     *
     * @param $IBlockId
     * @param $IBlockFields
     * @return void
     */
    protected function addPropertyFields($IBlockId, $IBlockFields)
    {
        $ufe = new \CIBlockPropertyEnum();

        $topSortProp = \CIBlockProperty::GetList(['sort' => 'desc', 'name' => 'asc'], ['IBLOCK_ID' => $IBlockId])->fetch()['SORT'];

        $count = !empty($topSortProp) ? $topSortProp : 0;

        $sort = $count + 10;
        global $CACHE_MANAGER;

        foreach ($IBlockFields as $fieldName => $fieldValue) {
            $typeAr = explode(':', $fieldValue[1]);
            $aUserField = array(
                'IBLOCK_ID' => $IBlockId,
                'CODE' => strtoupper($fieldName),
                'MULTIPLE' => !empty($fieldValue[2]['MULTIPLE']) ? $fieldValue[2]['MULTIPLE'] : 'N',
                'PROPERTY_TYPE' => $typeAr[0], //  S - строка, N - число, F - файл, L - список, E - привязка к элементам, G - привязка к группам.
                'USER_TYPE' => $typeAr[1] ?? '',
                'SORT' => $sort,
                'IS_REQUIRED' => $fieldValue[0],
                'NAME' => $fieldValue[2]['EDIT_FORM_LABEL'],
                'FILTRABLE' => 'N',
                'SEARCHABLE' => 'N',
                'FEATURES' => [
                    [
                        'MODULE_ID' => 'iblock',
                        'FEATURE_ID' => Iblock\Model\PropertyFeature::FEATURE_ID_LIST_PAGE_SHOW,
                        'IS_ENABLED' => 'Y'
                    ],
                    [
                        'MODULE_ID' => 'iblock',
                        'FEATURE_ID' => Iblock\Model\PropertyFeature::FEATURE_ID_DETAIL_PAGE_SHOW,
                        'IS_ENABLED' => 'Y'
                    ]
                ],
                'ACTIVE' => 'Y',
            );

            if (in_array($fieldValue[1], ['E', 'G', 'E:EList', 'S:ElementXmlID', 'E:EAutocomplete', 'iblock_element', 'iblock_section'])) {
                if (!empty($fieldValue[4])) {
                    $ib = explode(':', $fieldValue[4]);
                    if (!empty($ib[0]) && !empty($ib[1])) {
                        $iblock_id = $this->addIblock($ib[0], $ib[1], $ib[0]);
                        if (intval($iblock_id) > 0) {
                            $aUserField['LINK_IBLOCK_TYPE_ID'] = $ib[0];
                            $aUserField['LINK_IBLOCK_ID'] = $iblock_id;
                        }
                    }
                }
            }

            $addInList = false;
            if (isset($fieldValue[2]['IU_ADD_IN_LIST'])) {
                $addInList = $fieldValue[2]['IU_ADD_IN_LIST'];
                unset($fieldValue[2]['IU_ADD_IN_LIST']);
            }

            if (isset($fieldValue[2]) && is_array($fieldValue[2])) {
                $aUserField = array_merge($aUserField, $fieldValue[2]);
            }

            $iblockproperty = new \CIBlockProperty;
            $ufId = $iblockproperty->Add($aUserField);


            $CACHE_MANAGER->ClearByTag("create_property" . $IBlockId);;
            $sort += 10;
        }
    }


    /**
     * Add elemetst in iblock
     *
     * @param $iblockId
     * @param $element
     * @param $userCreateId
     * @return array
     */

    protected function addElementIblock($iblockId, $element, $userCreateId = 1)
    {
        $el = new \CIBlockElement;
        $result = [];
        try {
            $prop = [
                "ACTIVE" => "Y",
                "MODIFIED_BY" => $userCreateId,
                "IBLOCK_ID" => $iblockId,
                'NAME' => $element['NAME'],
                "PROPERTY_VALUES" => $element['PROPERTY_VALUES']
            ];
            $elId = $el->Add($prop);

            if ($elId) {
                $result['STATUS'] = 'success';
                $result['ID'] = $elId;
                $result['MSG'] = 'Элемент с id: ' . $elId . ' успешно добавлен';

            } else {
                $errorAddEl = $el->LAST_ERROR;
                $result['ERROR'] = 'Ошибка при добавлении элемента: ' . $el->LAST_ERROR . '<br>';

            }
            return $result;
        } catch (\Exception $e) {
            $result['ERROR'] = 'Критическая ошибка при добавлении элемента: ' . $el->LAST_ERROR . '<br>';
            return $result;
        }
    }

    /**
     * Check is exist elements in iblock
     *
     * @param $iblockCode
     * @return bool
     */
    protected function isExistElements($iblockCode)
    {

        $iblockTableName = $this->getTablePathIblock($iblockCode);

        $arRes = $iblockTableName::getList([

            'select' => ['ID'],
            'count_total' => true,
        ]);
        $result = $arRes->getCount();
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }

}