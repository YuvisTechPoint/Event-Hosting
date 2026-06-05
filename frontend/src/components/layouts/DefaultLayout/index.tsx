import {Outlet} from "react-router";
import {Header} from "../../common/Header";
import {Container} from "@mantine/core";
import {GlobalMenu} from "../../common/GlobalMenu";
import {ColorSchemeToggle} from "../../common/ColorSchemeToggle";
import ImpersonationBanner from "../../common/ImpersonationBanner";

const DefaultLayout = () => {
    return (
        <>
            <ImpersonationBanner />
            <Header rightContent={<><ColorSchemeToggle/><GlobalMenu/></>}/>
            <Container>
                <Outlet/>
            </Container>
        </>
    );
}

export default DefaultLayout;
