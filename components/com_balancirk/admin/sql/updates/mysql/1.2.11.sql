/**************************************************************************************************
*                                                                                                 *
*  SQL script to update students table                                                            *
*                                                                                                 *
*    added mutuality column                                                                       *
*                                                                                                 *
**************************************************************************************************/
ALTER TABLE `#__balancirk_students`
    ADD COLUMN `mutuality` varchar(100) DEFAULT NULL AFTER `birthdate`;
