# UC-27 — Logout

| Field | Description |
|---|---|
| Primary actor | Any authenticated Student Applicant, FASSG Personnel, Sponsor, Accounting Office Personnel, or System Administrator |
| Purpose | Ends the current authenticated session. |
| Preconditions | The user has an authenticated session. |
| Trigger | The user selects Sign Out / Logout. |
| Main flow | The user submits the logout action. The system ends authentication, invalidates the session, regenerates the CSRF token, and redirects to the configured unauthenticated destination. |
| Postconditions | The ended session no longer provides access to protected functions. Existing student, verification, application and sponsorship records are preserved. |
| Relationships | None. Logout is shared by all five roles. |

This adds an identifier for the Logout oval in the supplied diagram. UC-01–UC-26 retain their existing identifiers. The earlier description files are not overwritten.
