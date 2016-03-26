# WHMCS Registrar ODR

WHMCS Registrar ODR is a registrator for WHMCS and the opendomainregistry

#### Features

- [x] Registration and transfering of a domain, uses excisting contacts if aviable
- [x] Retrieve Transfer key
- [x] Retrieve and set NS information
- [x] Delete a domain (disable renew and mark it canceld in WHMCS)
- [x] Expiry Sync 
- [x] Contact Sync
- [x] Domain Sync
- [x] Transfer Sync

### Expiry Sync
This synchronisation will retrieve the domain expiry date from ODR anbd store this in in WHMCS. 
Invoices will be created at the right time before the domain expires.
##### **WARNING**: 
- It is possible that when the sync runs for the first time customers will get double or no invoices for a renewal period.

### Contact Sync
This synchronisation will retrieve the domain contact details from WHMCS and compares this with the information stored in ODR. After this 3 things can happen
- The name is different or the ODR contact is not valid -> a new ODR contact will be created and domain will be changed to use this contact
- The contact details are different (name, telephone, etc..) -> the ODR contact will be updated with the new information from WHMCS
- The contact details are equal -> No change will happen
##### **WARNING**:
- This sync changes handle registrant, on-site, tech and admin to point to a single contact. It is not possible to have different contact details for on-site, tech, admin or admin.
- When a new contact is created some registries will see this as a owner change and will send a email to the old owner. Except alot customer incidents!

### Domain Sync
This synchronisation will compare the active domains in WHMCS and ODR. When there is a mismatch a report email will be send to the primairy domain owner.

#### TODO
- Improve the contact lookup feature when registering or transfering of a domain
- Improve error logging
- Add ability to sync onsite, admin and tech contacts
- Add ability to report domain sync daily

### Instalation
1. Copy the files to {whmcs instalation}/registrars/ODR/
2. Activate the module in WHMCS: Setup>Product/Services>Domain Registrars>ODR
3. Configure the following fields:
###### Required:
- API Key: You can find this key in the ODR dasboard under "API Keys"
- API Secret: You can find this key in the ODR dasboard under "API Keys"
- Admin user: under wich user the module can run (a admin user)
###### Optional:
- Sync contact: Enable the Contact Sync
- Sync domain: Enable the Domain Sync
- Primairy domain: domain that will trigger the Domain Sync report


