import { DayPicker } from 'react-day-picker';
import { id } from 'react-day-picker/locale';
import 'react-day-picker/style.css';

export default function DateRangePicker({ selected, onSelect }) {
    return (
        <DayPicker
            mode="range"
            selected={selected}
            onSelect={onSelect}
            locale={id}
            weekStartsOn={1}
        />
    );
}
