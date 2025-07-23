export function getDates(startDate, stopDate) {
    const dateArray = [];
    let currentDate = startDate;

    while (currentDate.isSameOrBefore(stopDate)) {
        dateArray.push(currentDate);
        currentDate = currentDate.add(1, 'day');
    }

    return dateArray;
}
