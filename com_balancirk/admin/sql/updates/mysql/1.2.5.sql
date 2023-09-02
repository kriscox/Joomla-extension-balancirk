/**************************************************************************************************
*                                                                                                 * 
*  SQL script to upate lessons table                                                              * 
*                                                                                                 * 
*    added orderning column                                                                       * 
*                                                                                                 * 
**************************************************************************************************/
ALTER TABLE `#__balancirk_lessons` 
	ADD COLUMN `ordening` int(11) NOT NULL DEFAULT 0;