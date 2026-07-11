/**
 * @file
 * Initializes the combined staff booking calendar (task 0004).
 *
 * Uses the locally vendored FullCalendar 6 global bundle (task 0009)
 * attached via bat_fullcalendar's library. Events come from the
 * module's own JSON feed; FullCalendar appends start/end parameters
 * for the visible range itself. Events carrying a url (customer
 * bookings -> the order; staff blocks -> the remove-block confirm
 * form) navigate on click, FullCalendar's native behavior.
 */
(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.shhStaffBookingCalendar = {
    attach(context) {
      once('shhStaffBookingCalendar', '#shh-staff-calendar', context).forEach((el) => {
        const calendar = new FullCalendar.Calendar(el, {
          initialView: 'timeGridWeek',
          firstDay: 1,
          slotMinTime: '07:00:00',
          slotMaxTime: '21:00:00',
          allDaySlot: false,
          nowIndicator: true,
          height: 'auto',
          displayEventEnd: true,
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
          },
          events: {
            url: drupalSettings.shhStaffBookingCalendar.eventsUrl,
          },
        });
        calendar.render();
      });
    },
  };
})(Drupal, drupalSettings, once);
