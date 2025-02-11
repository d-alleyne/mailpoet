import classnames from 'classnames';
import ReactDatePicker, { ReactDatePickerProps } from 'react-datepicker';
import { MailPoet } from 'mailpoet';

type Props = ReactDatePickerProps & {
  dimension?: 'small';
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
};

export function Datepicker({
  dimension,
  isFullWidth,
  iconStart,
  iconEnd,
  ...props
}: Props) {
  return (
    <div
      className={classnames('mailpoet-datepicker mailpoet-form-input', {
        [`mailpoet-form-input-${dimension}`]: dimension,
        'mailpoet-disabled': props.disabled,
        'mailpoet-full-width': isFullWidth,
      })}
    >
      {iconStart}
      <ReactDatePicker
        useWeekdaysShort
        calendarStartDay={props.calendarStartDay ?? MailPoet.wpWeekStartsOn}
        {...props}
      />
      {iconEnd}
    </div>
  );
}
