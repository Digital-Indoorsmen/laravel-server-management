import pytest
import paramiko
import time

# Configuration for test nodes (matches Vagrantfile)
TEST_NODES = [
    {"hostname": "192.168.56.10", "username": "vagrant", "identity_file": "~/.vagrant.d/insecure_private_key"},
    {"hostname": "192.168.56.11", "username": "vagrant", "identity_file": "~/.vagrant.d/insecure_private_key"},
]

@pytest.fixture(params=TEST_NODES)
def ssh_client(request):
    node = request.param
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    # Attempt to connect (might require manual SSH key setup if not using Vagrant defaults)
    client.connect(
        hostname=node["hostname"],
        username=node["username"],
        key_filename=node["identity_file"]
    )
    yield client
    client.close()

def run_command(client, command):
    stdin, stdout, stderr = client.exec_command(command)
    exit_status = stdout.channel.recv_exit_status()
    return exit_status, stdout.read().decode().strip(), stderr.read().decode().strip()

def test_os_detection(ssh_client):
    status, stdout, stderr = run_command(ssh_client, "cat /etc/os-release")
    assert status == 0
    assert ("AlmaLinux" in stdout or "Rocky Linux" in stdout)

def test_core_packages_installed(ssh_client):
    packages = ["nginx", "mariadb-server", "postgresql-server", "redis", "fail2ban"]
    for pkg in packages:
        status, stdout, stderr = run_command(ssh_client, f"rpm -q {pkg}")
        assert status == 0, f"Package {pkg} is NOT installed"

def test_services_active(ssh_client):
    services = ["nginx", "mariadb", "postgresql", "redis", "fail2ban", "firewalld"]
    for svc in services:
        status, stdout, stderr = run_command(ssh_client, f"systemctl is-active {svc}")
        assert stdout == "active", f"Service {svc} is NOT active"

def test_user_panel_exists(ssh_client):
    status, stdout, stderr = run_command(ssh_client, "getent passwd panel")
    assert status == 0, "User 'panel' does not exist"
    
    status, stdout, stderr = run_command(ssh_client, "ls -ld /home/panel")
    assert "panel panel" in stdout, "Home directory /home/panel has incorrect ownership"

def test_directories_created(ssh_client):
    dirs = ["/home/panel/sites", "/var/log/panel", "/etc/ssl/panel"]
    for d in dirs:
        status, stdout, stderr = run_command(ssh_client, f"ls -d {d}")
        assert status == 0, f"Directory {d} was not created"

def test_selinux_mode(ssh_client):
    status, stdout, stderr = run_command(ssh_client, "getenforce")
    assert stdout in ["Permissive", "Enforcing"], "SELinux should not be Disabled (currently permissive is expected)"

def test_firewall_rules(ssh_client):
    status, stdout, stderr = run_command(ssh_client, "firewall-cmd --list-all")
    assert "ssh" in stdout
    assert "http" in stdout
    assert "https" in stdout
    assert "8095/tcp" in stdout

def test_fail2ban_sshd_jail(ssh_client):
    status, stdout, stderr = run_command(ssh_client, "fail2ban-client status sshd")
    assert status == 0
    assert "Status for the jail: sshd" in stdout

def test_php_fpm_versions(ssh_client):
    versions = ["74", "81", "82", "83", "84"]
    for ver in versions:
        status, stdout, stderr = run_command(ssh_client, f"systemctl is-active php{ver}-php-fpm")
        assert stdout == "active", f"PHP {ver} FPM is NOT active"

def test_idempotency(ssh_client):
    # This is a meta-test that would ideally run the setup script again and check for changes
    # For now, we manually verify no errors in log
    status, stdout, stderr = run_command(ssh_client, "grep 'Error:' /var/log/panel-setup.log")
    assert stdout == "", "Errors found in setup log"
