<?php

namespace App\Constants;

class ErrorCodes
{
    // 🔁 General (1000–1099)
    const INTERNAL_SERVER_ERROR = 1000;
    const UNKNOWN_ERROR = 1001;

    // 🔐 Auth / Tokens (1100–1199)
    const TOKEN_NOT_PROVIDED = 1101;
    const TOKEN_EXPIRED = 1102;
    const UNAUTHORIZED = 1103;

    // 📝 Validation (1200–1299)
    const EMAIL_REQUIRED = 1201;
    const PASSWORD_TOO_SHORT = 1202;
    const EMAIL_ALREADY_EXISTS = 1203;

    // 📝 Post/Feed Errors (1300–1399)
    const POST_SAVE_FAILED = 1300;

    // 🧑 User (1500–1599)
    const USER_NOT_REGISTERED = 1501;
    const INCOMPLETE_DATA = 1502;
    const INVALID_CREDENTIALS = 1503;

}
