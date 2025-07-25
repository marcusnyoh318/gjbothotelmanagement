**Hotel Management System by Team GJBot**  
**Prepared by Marcus, Leonard and Zayne**

---

**1.0 Functions**

**1.1 User Accounts**

**1.1.1 Account Creation**  
Users will be able to create an account by clicking the "Create Account" button at the top of the website.  
- Username: Must contain at least 3 characters; no spaces are allowed.  
- Password: Must contain at least 6 characters.  
Account information is stored securely and required for accessing booking features and saving history.

**1.1.2 User Log In**  
Users log in by providing their registered username and password.  
The system will verify credentials and redirect users to the main dashboard upon successful login.

**1.1.3 View Bookings**  
After logging in, users can view a list of all their bookings.  
- Each booking displays its current status: *Upcoming*, *Ongoing*, or *Past*.  
- Basic booking analytics are provided for user review, including total number of bookings made.

---

**1.2 Room Booking**

**1.2.1 Information Collection**  
To make a reservation, users are required to fill in the following details:  
- Full Name  
- Selected Room Type  
- Check-In Date  
- Check-Out Date  
After entering these details, users must click "Book Room" to continue.

**1.2.2 Payment**  
- Upon clicking "Book Room", the system generates detailed payment information:  
  • Room price (flat rate based on selected type)  
  • 10% tax  
- Users will then be prompted to provide their card information to process payment.  
*(Note: Payment system is simulated and not yet connected to a real payment gateway.)*

**1.2.3 Booking Confirmation**  
Once payment is successful:  
- A summary of the booking is shown, including room details and check-in/out dates.  
- The user is given options to:  
  • View the receipt  
  • View all bookings  
  • Book another room

**1.2.4 Receipt**  
A digital receipt is automatically generated after every booking.  
- Users may choose to save or print the receipt for future reference.  

**1.2.5 Linking with User Account**  
If a user is logged in when making a booking, the reservation details will be linked to their account and appear under "View My Bookings".

---

**1.3 Admin Page**

**1.3.1 Admin Log In**  
Admins can access the management dashboard using the following credentials:  
- Username: `admin`  
- Password: `admin123`  
Only authorized users will be granted access to the admin page.

**1.3.2 View Bookings**  
The admin panel displays:  
- A full list of all room bookings made by users  
- Functionality to delete specific bookings if needed (e.g., for cancellations or errors)

**1.3.3 Availability Calendar**  
An integrated availability calendar is generated on the admin page:  
- Shows room availability across all dates  
- Helps management prevent overbooking  
- Supports easier allocation of rooms during peak periods

**1.3.4 User Information**  
Admins can view the list of all registered users including:  
- Username  
- Password (hashed)  
To delete a user:  
- Open the `_User.txt` file  
- Locate the row containing the user’s name and hashed password  
- Manually remove that line from the file

---

**2.0 Implementation (Who Can Use the System?)**

**2.1 Hotel Management**  
- View all reservations in a central admin panel  
- Manage booking data, room availability, and calendar view  
- Monitor user accounts and system activity

**2.2 Hotel Clients**  
- Register and manage their account  
- Make hotel bookings easily from any location  
- Access their booking history and receipts without contacting hotel staff

---

**3.0 Future Improvements**

3.1 Integrate the system into the hotel’s official website for better promotion and branding  
3.2 Add detailed room pages that include photos, floorplans, and a list of amenities  
3.3 Introduce a membership system with point collection and tiered rewards  
3.4 Implement real payment gateway integrations (e.g., iPay88, Razer) for secure online transactions

---

**END OF _README.md_**  
**_Hotel Management System by GJBot_**
