# Testing — honest status

## Verified in the build environment (no PHP/MySQL was available there)

- JavaScript syntax check (`node --check script.js`): passed.
- Bracket / control-structure balance of every PHP file: passed (a rough script, NOT `php -l`).
- Every `url('route')` used in the code exists in the route table; every `require`d file exists; form field names match the controllers.
- The sample users' password hash matches the word `password` (checked with bcrypt).
- The "return to" validator accepts normal query strings and rejects `//evil.com`, `http://...` and header-injection text.

## NOT yet run: please test on your XAMPP and tick the boxes

| #   | Test                                                  | Expected                                                     | Result |
| --- | ----------------------------------------------------- | ------------------------------------------------------------ | ------ |
| 1   | Import `sql/sinulid.sql`                              | 4 tables + sample rows, no errors                            | ☐      |
| 2   | Sign up with a weak password                          | Error message, no account                                    | ☐      |
| 3   | Sign up with a taken username/email                   | "already taken" message                                      | ☐      |
| 4   | Sign up correctly (with and without a picture)        | Redirect to login, success banner                            | ☐      |
| 5   | Log in with wrong password                            | Generic "Invalid..." message                                 | ☐      |
| 6   | Log in correctly                                      | Feed opens; `password` column in DB starts with `$2y$`       | ☐      |
| 7   | Open `?r=feed` after logout                           | Redirected to login                                          | ☐      |
| 8   | Create a post (text only, then with image)            | Appears at top of feed                                       | ☐      |
| 9   | Upload a `.php` file renamed to `.jpg`                | Rejected                                                     | ☐      |
| 10  | Edit and delete your own post                         | Works; image file removed on delete                          | ☐      |
| 11  | Open `?r=post/edit&id=<someone else's post>`          | 403 error page                                               | ☐      |
| 12  | Add, edit, delete your own comment                    | Works; stays on same post                                    | ☐      |
| 13  | Like, refresh, unlike                                 | Count correct and persistent; DB has max 1 row per user+post | ☐      |
| 14  | Edit profile (name, bio, picture)                     | Saved; panel and posts show new data                         | ☐      |
| 15  | Search a name, a username, a keyword, nonsense        | Matches / "No ... matched"                                   | ☐      |
| 16  | Post text like `<script>alert(1)</script>`            | Shown as text, not executed                                  | ☐      |
| 17  | Submit a form with the CSRF field removed (dev tools) | 419 error page                                               | ☐      |
| 18  | Dark mode toggle, refresh                             | Theme persists                                               | ☐      |
| 19  | Phone-width browser window                            | Layout stacks, nothing overflows                             | ☐      |
