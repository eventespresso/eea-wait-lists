## Expectations
###  `waitlist_can_register` message type.

- Is labelled "Registration Promoted To Wait-list Notification" in the message template group list table.
- Triggered whenever a registration is promoted from the Waitlist status (RWL) to any registration status that allows payment (eg. RPP).
- Regardless of where its triggered, there is one message generated per registration.
- It should only be triggered from the context of the Registration Details page or the Registration List table if the logged in user has explicitly requested messages to be sent (i.e. selecting "Set registrations to Pending Payment and notify" option in the bulk action dropdown for selected registrations in the waitlist).

### `registration_demoted_to_waitlist` message type

- Is labelled "Registration Demoted To Wait-list Notification" in the message template group list table.
- Triggered whenever a registration is demoted from any registration status that allows payment (e.g. RPP) to waitlist status (RWL).
- Regardless of where its triggered, there is one message generated per registration.
-  It should only be triggered from the context of the Registration Details page or the Registration List table if the logged in user has explicitly requested messages to be sent (i.e. selecting "Set registrations to Waitlist and notify" option in the bulk action dropdown for selected registrations that start with a status allowing payment (i.e. pending payment)).

### `registration_added_to_waitlist`

- Is labelled "Registration Added To Wait-list Notification"
- Triggered on the frontend for any registrations created when a person signs up for the waitlist and on the backend for any registrations manually changed from a registration status that does NOT allow payments (e.g. Not Approved) to the Waitlist status (RWL)
- It effectively behaves the same as Registration type messages (i.e. Registration Approved messages) and thus will automatically condense multiple registrations into one email per attendee attached to those registrations when possible.
-  It should only be triggered from the context of the Registration Details page or the Registration List table if the logged in user has explicitly requested messages to be sent (i.e. selecting "Set registrations to Waitlist and notify" option in the bulk action dropdown for selected registrations that start with a status NOT allowing payment (i.e. not approved)).

For all new message types:

* [ ] Verify preview generation works and there are no errors.
* [ ] For each message type described in the "What changed" section above, verify that the expectations listed in the description match behaviour while testing in all contexts (i.e. triggering messages from the frontend (or auto-promotion) and triggering messages from the backend).