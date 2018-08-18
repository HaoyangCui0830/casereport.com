<?php
/**
 * Created by PhpStorm.
 * User: DennyLee
 * Date: 2018/8/16
 * Time: 18:17
 */

class ErrorCode{
    const USERNAME_EXISTS = 1;
    const PASSWORD_CANNOT_EMPTY = 2;
    const USERNAME_CANNOT_EMPTY = 3;
    const REGISTER_FAIL = 4;
    const USERNAME_OR_PASSWORD_INVALID = 5;
    const TITLE_CANNOT_EMPTY = 6;
    const CONTENT_CANNOT_EMPTY = 7;
    const REPORT_FAIL =8;
    const CASE_ID_CANNOT_EMPTY = 9;
    const CASE_NOT_FOUND = 10;
    const PERMISSION_DENIED = 11;
    const CASE_EDIT_FAIL = 12;
    const CASE_DELETE_FAIL = 13;
    const PAGE_SIZE_TO_BIG = 14;
    const SEVER_INTERNAL_ERROR = 15;
    const USER_NOT_FOUND = 16;
    const USER_UPDATE_FAIL = 17;
}