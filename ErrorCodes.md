# 📘 Error Codes – API & Frontend

This table documents all the custom error codes used to safely and clearly communicate errors between the backend (Laravel) and the frontend (React/Firebase).

---

## 🔁 General (1000–1099)

| Code  | Message                  | Description                               |
|-------|--------------------------|-------------------------------------------|
| 1000  | Internal server error    | Unexpected server-side error              |
| 1001  | Unknown error            | Fallback for uncategorized errors         |

---

## 🔐 Auth / Tokens (1100–1199)

| Code  | Message                  | Description                               |
|-------|--------------------------|-------------------------------------------|
| 1101  | Token not provided       | The frontend did not send the JWT token   |
| 1102  | Token expired            | The session token has expired             |
| 1103  | Unauthorized             | Access without valid permissions          |

---

## 📝 Validation (1200–1299)

| Code  | Message                  | Description                               |
|-------|--------------------------|-------------------------------------------|
| 1201  | Email is required        | Email field is empty or missing           |
| 1202  | Password too short       | Password does not meet length requirement |
| 1203  | Email already exists     | Email already exists in the database      |

---

## 📝 Post/Feed Errors (1300–1399)

| Code  | Message                  | Description                               |
|-------|--------------------------|-------------------------------------------|
| 1300  | Failed to save post        | Failed to save post           |

---

## 🧑 User (1500–1599)

| Code  | Message                  | Description                               |
|-------|--------------------------|-------------------------------------------|
| 1501  | User not registered      | Email not found in the database           |
| 1502  | Incomplete data          | Missing required fields during registration |
| 1503  | Invalid credentials    | Incorrect email or password               |

