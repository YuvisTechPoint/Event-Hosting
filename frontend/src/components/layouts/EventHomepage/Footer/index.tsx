import classes from './Footer.module.scss';
import {PoweredByFooter} from "../../../common/PoweredByFooter";
import {Text} from "@mantine/core";
import {Link} from "react-router";
import {t} from "@lingui/macro";

export const Footer = () => {
    return (
        /**
         * (c) Event Hosting 2025
         *
         * PLEASE NOTE:
         *
         * Event Hosting is licensed under the GNU Affero General Public License (AGPL) version 3.
         *
         * You can find the full license text at: https://github.com/HiEventsDev/hi.events/blob/main/LICENCE
         *
         * In accordance with Section 7(b) of the AGPL, we ask that you retain the "Powered by Event Hosting" notice.
         *
         * If you wish to remove this notice, a commercial license is available at: https://hi.events/licensing
         */
        <footer className={classes.footer}>
            <PoweredByFooter/>
            <Text size="sm" ta="center" mt="xs">
                <Link to="/discover">{t`Discover events`}</Link>
            </Text>
        </footer>
    )
}
