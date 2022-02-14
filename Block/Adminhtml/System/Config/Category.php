<?php
/**
 * Copyright © Moip by PagSeguro. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See COPYING.txt for license details.
 */

namespace Moip\Magento2\Block\Adminhtml\System\Config;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Category - Defines Category types.
 */
class Category implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            null                                         => __('Select the store segment.'),
            'ANIMALS_AND_PET_SUPPLIES'                   => __('Animals and Pet Supplies'),
            'APPAREL_AND_ACCESSORIES'                    => __('Apparel and Accessories'),
            'CLOTHING'                                   => __('Apparel and Accessories > Clothing'),
            'CLOTHING_ACCESSORIES'                       => __('Apparel and Accessories > Clothing Accessories'),
            'COSTUMES_AND_ACCESSORIES'                   => __('Apparel and Accessories > Costumes and Accessories'),
            // phpcs:ignore Generic.Files.LineLength
            'HANDBAGS_AND_WALLET_ACCESSORIES'            => __('Apparel and Accessories > Handbags and Wallet Accessories'),
            // phpcs:ignore Generic.Files.LineLength
            'HANDBAGS_WALLETS_AND_CASES'                 => __('Apparel and Accessories > Handbags and Wallet Accessories'),
            'JEWELRY'                                    => __('Apparel and Accessories > Jewelry'),
            'SHOE_ACCESSORIES'                           => __('Apparel and Accessories > Shoe Accessories'),
            'SHOES'                                      => __('Apparel and Accessories > Shoes'),
            'OTHER_APPAREL'                              => __('Apparel and Accessories > Other'),
            'ARTS_AND_ENTERTAINMENT'                     => __('Arts and Entertainment'),
            'BABY_AND_TODDLER'                           => __('Baby and Toddler'),
            'BUSINESS_AND_INDUSTRIAL'                    => __('Business and Industrial'),
            'ADVERTISING_AND_MARKETING'                  => __('Business and Industrial > Advertising and Marketing'),
            'AGRICULTURE'                                => __('Business and Industrial > Agriculture'),
            'CONSTRUCTION'                               => __('Business and Industrial > Construction'),
            'FILM_AND_TELEVISION'                        => __('Business and Industrial > Film and Television'),
            'FINANCE_AND_INSURANCE'                      => __('Business and Industrial > Finance and Insurance'),
            'FOOD_SERVICE'                               => __('Business and Industrial > Food Service'),
            'FORESTRY_AND_LOGGING'                       => __('Business and Industrial > Forestry and Logging'),
            'HEAVY_MACHINERY'                            => __('Business and Industrial > Heavy Machinery'),
            'HOTEL_AND_HOSPITALITY'                      => __('Business and Industrial > Hotel and Hospitality'),
            'INDUSTRIAL_STORAGE'                         => __('Business and Industrial > Industrial Storage'),
            'LAW_ENFORCEMENT'                            => __('Business and Industrial > Law Enforcement'),
            'MANUFACTURING'                              => __('Business and Industrial > Manufacturing'),
            'MATERIAL_HANDLING'                          => __('Business and Industrial > Material Handling'),
            'MEDICAL'                                    => __('Business and Industrial > Medical'),
            'MINING_AND_QUARRYING'                       => __('Business and Industrial > Mining and Quarrying'),
            'PIERCING_AND_TATTOOING'                     => __('Business and Industrial > Piercing and Tattooing'),
            'RETAIL'                                     => __('Business and Industrial > Retail'),
            'SCIENCE_AND_LABORATORY'                     => __('Business and Industrial > Science and Laboratory'),
            'SIGNAGE'                                    => __('Business and Industrial > Signage'),
            'WORK_SAFETY_PROTECTIVE_GEAR'                => __('Business and Industrial > Work Safety Protective Gear'),
            'OTHER_BUSINESSES'                           => __('Business and Industrial > Other Businesses'),
            'CAMERA_AND_OPTIC_ACCESSORIES'               => __('Camera and Optic Accessories'),
            'CAMERAS'                                    => __('Camera and Optic Accessories > Cameras'),
            'CAMERA_ACESSORIES'                          => __('Camera and Optic Accessories > Camera Acessories'),
            'PHOTOGRAPHY'                                => __('Camera and Optic Accessories > Photography'),
            // phpcs:ignore Generic.Files.LineLength
            'OTHERS_CAMERAS_ACCESSORIES'                 => __('Camera and Optic Accessories > Others Cameras Accessories'),
            'ELECTRONICS'                                => __('Electronics'),
            '3D_PRINTERS'                                => __('Electronics > 3d Printers'),
            'AUDIO'                                      => __('Electronics > Audio'),
            'CIRCUIT_BOARDS_AND_COMPONENTS'              => __('Electronics > Circuit Boards and Components'),
            'COMMUNICATIONS'                             => __('Electronics > Communications'),
            'COMPONENTS'                                 => __('Electronics > Components'),
            'COMPUTERS'                                  => __('Electronics > Computers'),
            'ELECTRONICS_ACCESSORIES'                    => __('Electronics > Electronics Accessories'),
            'GPS_NAVIGATION_SYSTEMS'                     => __('Electronics > Gps Navigation Systems'),
            'GPS_ACCESSORIES'                            => __('Electronics > Gps Accessories'),
            'NETWORKING'                                 => __('Electronics > Networking'),
            'PRINT_COPY_SCAN_AND_FAX'                    => __('Electronics > Print Copy Scan and Fax'),
            // phpcs:ignore Generic.Files.LineLength
            'PRINTER_COPIER_AND_FAX_MACHINE_ACCESSORIES' => __('Electronics > Printer Copier and Fax Machine Accessories'),
            'VIDEO'                                      => __('Electronics > Video'),
            'VIDEO_GAME_CONSOLES'                        => __('Electronics > Video Game Consoles'),
            'VIDEO_GAME_CONSOLE_ACCESSORIES'             => __('Electronics > Video Game Console Accessories'),
            'OTHER_ELECTRONICS'                          => __('Electronics > Other Electronics'),
            'FOOD_BEVERAGES_AND_TOBACCO'                 => __('Food Beverages and Tobacco'),
            'FURNITURE'                                  => __('Furniture'),
            'TOOL_ACCESSORIES'                           => __('Tool Accessories'),
            'HEALTH_AND_BEAUTY'                          => __('Health and Beauty'),
            'PERFUME_AND_COLOGNE'                        => __('Health and Beauty > Perfume and Cologne'),
            'MAKEUP'                                     => __('Health and Beauty > Makeup'),
            'BATH_AND_BODY'                              => __('Health and Beauty > Bath and Body'),
            'COSMETIC_TOOLS'                             => __('Health and Beauty > Cosmetic Tools'),
            'LUGGAGE_AND_BAGS'                           => __('Luggage and Bags'),
            'ADULT'                                      => __('Adult'),
            'WEAPONS_AND_AMMUNITION'                     => __('Weapons and Ammunition'),
            'OFFICE_SUPPLIES'                            => __('Office Supplies'),
            'RELIGIOUS_AND_CEREMONIAL'                   => __('Religious and Ceremonial'),
            'SOFTWARE'                                   => __('Software'),
            'COMPUTER_SOFTWARE'                          => __('Software > Computer Software'),
            'DIGITAL_GOODS_AND_CURRENCY'                 => __('Software > Digital Goods and Currency'),
            'DIGITAL_SERVICES'                           => __('Software > Digital Services'),
            'VIDEO_GAME_SOFTWARE'                        => __('Software > Video Game Software'),
            'OTHER_SOFTWARES'                            => __('Software > Other Softwares'),
            'SPORTING_GOODS'                             => __('Sporting Goods'),
            'TOYS_AND_GAMES'                             => __('Toys and Games'),
            'VEHICLES_AND_PARTS'                         => __('Vehicles and Parts'),
            'BOOKS'                                      => __('Books'),
            'DVDS_AND_VIDEOS'                            => __('Dvds and Videos'),
            'MAGAZINES_AND_NEWSPAPERS'                   => __('Magazines and Newspapers'),
            'MUSIC'                                      => __('Music'),
            'CDS_AND_LPS'                                => __('Music > Cds and Lps'),
            'MUSICAL_INSTRUMENTS'                        => __('Music > Musical Instruments'),
            'DIGITAL_MUSIC'                              => __('Music > Digital Music'),
            'OTHER_MUSIC_ITEMS'                          => __('Music > Other Music Items'),
            'OTHER_CATEGORIES'                           => __('Music > Other Categories'),
        ];
    }
}
